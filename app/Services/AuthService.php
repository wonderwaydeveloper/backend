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

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private EmailService $emailService
    ) {
    }

    /**
     * Register a new user
     */
    public function register(UserRegistrationDTO $dto): array
    {
        $user = User::create([
            'name' => $dto->name,
            'username' => $dto->username,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'date_of_birth' => $dto->dateOfBirth,
        ]);

        // Check if user is under 18
        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $user->update(['is_child' => true]);
        }

        $user->assignRole('user');

        // Send welcome email
        $this->emailService->sendWelcomeEmail($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Login user with credentials
     */
    public function login(LoginDTO $loginDTO): array
    {
        // Find user by email or username
        $user = User::where('email', $loginDTO->login)
                   ->orWhere('username', $loginDTO->login)
                   ->first();
        
        // Use hash_equals to prevent timing attacks
        $validCredentials = $user && Hash::check($loginDTO->password, $user->password);
        
        // Always perform hash check even if user doesn't exist to prevent timing attacks
        if (!$user) {
            Hash::check('dummy-password', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
        }

        if (!$validCredentials) {
            throw new ValidationException([
                'login' => ['Invalid login credentials'],
            ]);
        }

        // Check 2FA requirement
        if ($user->two_factor_enabled && !$loginDTO->twoFactorCode) {
            $tempToken = $user->createToken('temp_2fa')->plainTextToken;
            return [
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => 'Two-factor authentication required',
            ];
        }

        // Verify 2FA if provided
        if ($user->two_factor_enabled && $loginDTO->twoFactorCode) {
            return $this->handle2FA($user, $loginDTO->twoFactorCode);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * Logout user by deleting current token
     */
    public function logout(User $user): bool
    {
        $user->currentAccessToken()->delete();
        return true;
    }

    public function refreshToken(string $refreshToken): array
    {
        // Find all users and check refresh token
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

        // Generate new tokens
        $newToken = $user->createToken('auth_token')->plainTextToken;
        $newRefreshToken = Str::random(60);
        
        $user->update(['refresh_token' => Hash::make($newRefreshToken)]);

        return [
            'token' => $newToken,
            'refresh_token' => $newRefreshToken
        ];
    }

    public function forgotPassword(string $email): bool
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            return true; // Don't reveal if email exists
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        $this->emailService->sendPasswordResetEmail($user, $code);
        
        return true;
    }

    public function resetPassword(string $code, string $password): bool
    {
        $tokenRecords = \DB::table('password_reset_tokens')->get();
        $validRecord = null;
        
        foreach ($tokenRecords as $record) {
            if (Hash::check($code, $record->token)) {
                $validRecord = $record;
                break;
            }
        }

        if (!$validRecord || now()->diffInMinutes($validRecord->created_at) > 15) {
            return false;
        }

        $user = User::where('email', $validRecord->email)->first();
        if (!$user) {
            return false;
        }

        $user->update(['password' => Hash::make($password)]);
        \DB::table('password_reset_tokens')->where('email', $validRecord->email)->delete();
        
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
        
        return true;
    }

    public function resendVerification(User $user): bool
    {
        if ($user->hasVerifiedEmail()) {
            return false;
        }

        $token = Str::random(60);
        $user->update(['email_verification_token' => Hash::make($token)]);
        
        $this->emailService->sendVerificationEmail($user, $token);
        
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

    /**
     * Get current user data
     */
    public function getCurrentUser(User $user): User
    {
        return $user;
    }

    /**
     * Handle 2FA verification
     */
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

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ];
    }
}
