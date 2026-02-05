<?php

namespace App\Services;

use App\Contracts\Services\AuthServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Exceptions\ValidationException;
use App\DTOs\LoginDTO;
use App\DTOs\UserRegistrationDTO;
use PragmaRX\Google2FA\Google2FA;
use App\Services\{TokenManagementService, AuditTrailService};

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private EmailService $emailService,
        private TokenManagementService $tokenService,
        private AuditTrailService $auditService,
        private RateLimitingService $rateLimiter,
        private SecurityMonitoringService $securityService,
        private SessionTimeoutService $timeoutService,
        private PasswordSecurityService $passwordService
    ) {
    }

    public function register(UserRegistrationDTO $dto, ?\Illuminate\Http\Request $request = null): array
    {
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.register', $dto->email);
        
        if (!$rateLimitResult['allowed']) {
            $this->auditService->logSecurityEvent('rate_limit_exceeded', [
                'type' => 'auth.register',
                'identifier' => $dto->email,
                'attempts' => $rateLimitResult['attempts']
            ], $request);
            
            throw new ValidationException([
                'email' => ['Too many registration attempts. Please try again later.'],
            ]);
        }

        // Create user with basic data first
        $user = User::create([
            'name' => $dto->name,
            'username' => $dto->username,
            'email' => $dto->email,
            'date_of_birth' => $dto->dateOfBirth,
            'password' => 'temp', // Temporary password
        ]);

        // Use PasswordSecurityService for consistent password handling
        try {
            $this->passwordService->updatePassword($user, $dto->password);
        } catch (\InvalidArgumentException $e) {
            // If password validation fails, delete the user and throw exception
            $user->delete();
            throw new ValidationException([
                'password' => [$e->getMessage()],
            ]);
        }

        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $user->update(['is_child' => true]);
        }

        $user->assignRole('user');

        $this->auditService->logAuthEvent('register', $user, [
            'registration_method' => 'email',
            'is_child' => $user->is_child
        ]);

        $token = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;

        return [
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(LoginDTO $loginDTO, bool $createToken = true, ?\Illuminate\Http\Request $request = null): array
    {
        $identifier = $loginDTO->login;
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.login', $identifier);
        
        if (!$rateLimitResult['allowed']) {
            $this->auditService->logSecurityEvent('rate_limit_exceeded', [
                'type' => 'auth.login',
                'identifier' => $identifier,
                'attempts' => $rateLimitResult['attempts']
            ], $request);
            
            throw new ValidationException([
                'login' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $user = User::where('email', $loginDTO->login)
                   ->orWhere('username', $loginDTO->login)
                   ->orWhere('phone', $loginDTO->login)
                   ->first();
        
        $validCredentials = $user && Hash::check($loginDTO->password, $user->password);
        
        if (!$user) {
            Hash::check('dummy-password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        }

        if (!$validCredentials) {
            $this->auditService->logAuthEvent('failed_login', $user ?? new User(['email' => $loginDTO->login]), [
                'login_attempt' => $loginDTO->login,
                'failure_reason' => 'invalid_credentials',
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent()
            ], $request);
            
            if ($user && $request) {
                $suspiciousActivity = $this->securityService->checkSuspiciousActivity($user->id);
                if ($suspiciousActivity['risk_level'] === 'high') {
                    $this->auditService->logSecurityEvent('suspicious_login_pattern', [
                        'user_id' => $user->id,
                        'risk_score' => $suspiciousActivity['risk_score'],
                        'reasons' => $suspiciousActivity['reasons']
                    ], $request);
                }
            }
            
            throw new ValidationException([
                'login' => ['Invalid login credentials'],
            ]);
        }

        if (!$createToken) {
            return ['user' => $user];
        }

        $this->tokenService->enforceConcurrentSessionLimits($user);

        $this->auditService->logAuthEvent('login', $user, [
            'login_method' => 'credentials',
            'session_count' => $user->tokens()->count()
        ]);

        $token = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;

        return [
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): bool
    {
        $this->auditService->logAuthEvent('logout', $user);
        $user->currentAccessToken()->delete();
        return true;
    }

    public function logoutFromAllDevices(User $user): int
    {
        $revokedCount = $this->tokenService->revokeAllUserSessions($user);
        
        $this->auditService->logAuthEvent('logout_all', $user, [
            'sessions_revoked' => $revokedCount
        ]);
        
        return $revokedCount;
    }

    public function getUserSessions(User $user): array
    {
        return $this->tokenService->getUserActiveSessions($user);
    }

    public function revokeSession(User $user, string $tokenId): bool
    {
        return $this->tokenService->revokeSession($user, $tokenId);
    }

    public function refreshToken(string $refreshToken): array
    {
        $users = User::whereNotNull('refresh_token')->get();
        $user = null;
        
        foreach ($users as $u) {
            if (Hash::check($refreshToken, $u->refresh_token)) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            throw new ValidationException(['token' => ['Invalid refresh token']]);
        }

        $newToken = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
        $newRefreshToken = Str::random(60);
        
        $user->update([
            'refresh_token' => Hash::make($newRefreshToken)
        ]);

        $this->auditService->logAuthEvent('token_refreshed', $user);

        return [
            'token' => $newToken,
            'refresh_token' => $newRefreshToken
        ];
    }

    public function forgotPassword(string $email, ?\Illuminate\Http\Request $request = null): bool
    {
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.password_reset', $email);
        
        if (!$rateLimitResult['allowed']) {
            $this->auditService->logSecurityEvent('rate_limit_exceeded', [
                'type' => 'auth.password_reset',
                'identifier' => $email,
                'attempts' => $rateLimitResult['attempts']
            ], $request);
            
            return true;
        }

        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return true;
        }

        $code = random_int(100000, 999999);
        $expiryMinutes = $this->timeoutService->getPasswordResetExpiry();
        
        // Store in both database and cache for consistency
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            [
                'token' => Hash::make($code),
                'created_at' => now()
            ]
        );
        
        // Also store in cache for verify endpoint
        \Cache::put("password_reset:{$email}", [
            'code' => (string)$code,
            'expires_at' => now()->addMinutes($expiryMinutes)->timestamp
        ], now()->addMinutes($expiryMinutes));

        $this->emailService->sendPasswordResetEmail($user, $code);
        $this->auditService->logAuthEvent('password_reset_requested', $user);
        
        return true;
    }

    public function resetPassword(string $code, string $password, ?\Illuminate\Http\Request $request = null, ?string $email = null): bool
    {
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.reset_verify', $code);
        
        if (!$rateLimitResult['allowed']) {
            $this->auditService->logSecurityEvent('rate_limit_exceeded', [
                'type' => 'auth.reset_verify',
                'identifier' => 'code_attempt',
                'attempts' => $rateLimitResult['attempts']
            ], $request);
            
            return false;
        }

        $resetExpiry = $this->timeoutService->getPasswordResetExpiry();
        $records = \DB::table('password_reset_tokens')
                     ->where('created_at', '>', now()->subMinutes($resetExpiry));
        
        // If email provided, filter by email for better performance
        if ($email) {
            $records = $records->where('email', $email);
        }
        
        $records = $records->get();
        
        $validRecord = null;
        foreach ($records as $record) {
            if (Hash::check($code, $record->token)) {
                $validRecord = $record;
                break;
            }
        }

        if (!$validRecord) {
            return false;
        }

        $user = User::where('email', $validRecord->email)->first();
        if (!$user) {
            return false;
        }

        // Use PasswordSecurityService for password history and validation
        try {
            $this->passwordService->updatePassword($user, $password);
        } catch (\InvalidArgumentException $e) {
            // Log the validation failure
            $this->auditService->logSecurityEvent('password_reset_validation_failed', [
                'user_id' => $user->id,
                'reason' => $e->getMessage()
            ], $request);
            return false;
        }

        // Clean up both database and cache
        \DB::table('password_reset_tokens')->where('email', $validRecord->email)->delete();
        \Cache::forget("password_reset:{$validRecord->email}");
        
        // Revoke all active sessions for security
        $this->tokenService->revokeAllUserSessions($user);
        
        $this->auditService->logAuthEvent('password_reset', $user, [
            'sessions_revoked' => true,
            'password_history_checked' => true
        ]);
        
        return true;
    }

    public function verifyEmail(string $token): bool
    {
        $users = User::whereNotNull('email_verification_token')
                    ->whereNull('email_verified_at')
                    ->get();
        
        $user = null;
        foreach ($users as $u) {
            if (Hash::check($token, $u->email_verification_token)) {
                $user = $u;
                break;
            }
        }
        
        if (!$user) {
            return false;
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);
        
        $this->auditService->logAuthEvent('email_verified', $user);
        
        return true;
    }

    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $token = Str::random(60);
        
        $user->update([
            'email_verification_token' => Hash::make($token)
        ]);
        
        $this->emailService->sendVerificationEmail($user, $token);
        $this->auditService->logAuthEvent('verification_resent', $user);
        
        return true;
    }

    public function enable2FA(User $user): array
    {
        $twoFactorService = app(\App\Services\TwoFactorService::class);
        
        $secret = $twoFactorService->generateSecret();
        $qrCodeUrl = $twoFactorService->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $user->update(['two_factor_secret' => encrypt($secret)]);

        return [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl
        ];
    }

    public function verify2FA(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        $twoFactorService = app(\App\Services\TwoFactorService::class);
        $secret = decrypt($user->two_factor_secret);
        
        if ($twoFactorService->verifyCode($secret, $code)) {
            $user->update(['two_factor_enabled' => true]);
            return true;
        }
        
        return false;
    }

    public function disable2FA(User $user): bool
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null
        ]);
        
        return true;
    }

    public function getCurrentUser(User $user): User
    {
        return $user;
    }

    private function handle2FA(User $user, ?string $twoFactorCode): array
    {
        if (! $twoFactorCode) {
            return [
                'requires_2fa' => true,
                'message' => 'Two-factor authentication code required',
                'status' => 403,
            ];
        }

        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();

        if (! $google2fa->verifyKey($secret, $twoFactorCode)) {
            throw new ValidationException([
                'two_factor_code' => ['Invalid two-factor authentication code'],
            ]);
        }

        $token = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;

        return [
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ];
    }
}