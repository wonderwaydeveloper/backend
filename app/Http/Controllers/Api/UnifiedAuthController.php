<?php

namespace App\Http\Controllers\Api;

use App\DTOs\LoginDTO;
use App\Events\UserRegistered;
use App\Http\Controllers\Controller;
use App\Http\Requests\{LoginRequest, PhoneLoginRequest, PhoneRegisterRequest};
use App\Models\{User, PhoneVerificationCode};
use App\Services\{AuthService, EmailService, SmsService, TwoFactorService, PasswordSecurityService, DeviceFingerprintService};
use App\Rules\{StrongPassword, MinimumAge, ValidUsername};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UnifiedAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private EmailService $emailService,
        private SmsService $smsService,
        private TwoFactorService $twoFactorService,
        private PasswordSecurityService $passwordService,
        private \App\Services\RateLimitingService $rateLimiter,
        private VerificationCodeService $verificationCodeService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $loginDTO = LoginDTO::fromRequest($request->validated());
        
        // Rate limiting handled by UnifiedSecurityMiddleware
        
        if ($request->has('two_factor_code')) {
            return $this->verify2FALogin($loginDTO, $request->two_factor_code);
        }
        
        $result = $this->authService->login($loginDTO);
        
        if (isset($result['user']) && $result['user']->two_factor_enabled) {
            return response()->json([
                'requires_2fa' => true,
                'message' => 'Two-factor authentication required'
            ]);
        }
        
        if (isset($result['user'])) {
            $user = $result['user'];
            
            // Check password age (Twitter standard)
            if ($this->passwordService->isPasswordExpired($user)) {
                return response()->json([
                    'requires_password_change' => true,
                    'message' => 'Your password has expired. Please change it.',
                    'user_id' => $user->id
                ], Response::HTTP_FORBIDDEN);
            }
            
            $fingerprint = DeviceFingerprintService::generate($request);
            
            $lockKey = "device_check:{$user->id}:{$fingerprint}";
            $lock = Cache::lock($lockKey, 30);
            
            if ($lock->get()) {
                try {
                    $trustedDevice = $user->devices()->where('fingerprint', $fingerprint)->where('is_trusted', true)->first();
                    
                    if (!$trustedDevice) {
                        $code = $this->verificationCodeService->generateCode();
                        
                        $verificationData = [
                            'code' => $code,
                            'user_id' => $user->id,
                            'fingerprint' => $fingerprint,
                            'device_info' => [
                                'ip' => $request->ip(),
                                'user_agent' => $request->userAgent(),
                                'location' => 'Unknown Location'
                            ],
                            'code_sent_at' => now()->timestamp,
                            'expires_at' => $this->verificationCodeService->getCodeExpiryTimestamp(),
                            'resend_count' => 0
                        ];
                        
                        $cacheKey = "device_verification_by_fingerprint:{$fingerprint}";
                        Cache::put($cacheKey, $verificationData, now()->addMinutes($this->verificationCodeService->getExpiryMinutes()));
                        
                        if ($user->email) {
                            $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
                        } elseif ($user->phone) {
                            $this->smsService->sendVerificationCode($user->phone, $code);
                        }
                        
                        return response()->json([
                            'requires_device_verification' => true,
                            'user_id' => $user->id,
                            'fingerprint' => $fingerprint,
                            'message' => 'Device verification required. Check your email for verification code.',
                            'code_expires_at' => $verificationData['expires_at'],
                            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp()
                        ]);
                    }
                } finally {
                    $lock->release();
                }
            } else {
                return response()->json(['error' => 'System busy, please try again'], Response::HTTP_SERVICE_UNAVAILABLE);
            }
            
            $auditService = app(\App\Services\AuditTrailService::class);
            $auditService->logSecurityEvent('authentication_success', [
                'user_id' => $result['user']->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ], $request);
        }
        
        return response()->json($result);
    }
    
    private function verify2FALogin(LoginDTO $loginDTO, string $twoFactorCode): JsonResponse
    {
        $result = $this->authService->login($loginDTO, false);
        
        if (!isset($result['user'])) {
            return response()->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
        
        $user = $result['user'];
        
        if (!$user->two_factor_enabled) {
            return response()->json(['error' => '2FA not enabled'], Response::HTTP_BAD_REQUEST);
        }
        
        $secret = decrypt($user->two_factor_secret);
        $valid = $this->twoFactorService->verifyCode($secret, $twoFactorCode);
        
        if (!$valid) {
            return response()->json(['error' => 'Invalid 2FA code'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $token = app(\App\Services\SessionTimeoutService::class)->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Logout successful']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $deletedSessions = $this->authService->logoutFromAllDevices($request->user());
        
        return response()->json([
            'message' => 'Logged out from all devices',
            'sessions_revoked' => $deletedSessions
        ]);
    }

    public function getSessions(Request $request): JsonResponse
    {
        $sessions = $this->authService->getUserSessions($request->user());
        
        return response()->json($sessions);
    }

    public function revokeSession(Request $request): JsonResponse
    {
        $request->validate(['token_id' => 'required|string']);
        
        $success = $this->authService->revokeSession($request->user(), $request->token_id);
        
        if (!$success) {
            return response()->json(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json(['message' => 'Session revoked successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        // Rate limiting handled by UnifiedSecurityMiddleware
        $user = $this->authService->getCurrentUser($request->user());
        return response()->json($user);
    }

    public function multiStepStep1(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:' . config('validation.user.name.max_length', 50),
            'date_of_birth' => ['required', 'date', config('validation.date.before_rule', 'before:today'), new MinimumAge()],
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        $sessionId = $this->verificationCodeService->generateSessionId();
        $code = $this->verificationCodeService->generateCode();

        if (User::where($request->contact_type, $request->contact)->exists()) {
            return response()->json(['error' => 'Unable to complete registration'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Cache::put("registration:{$sessionId}", [
            'name' => $request->name,
            'date_of_birth' => $request->date_of_birth,
            'contact' => $request->contact,
            'contact_type' => $request->contact_type,
            'code' => $code,
            'step' => 1,
            'verified' => false,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => $this->verificationCodeService->getCodeExpiryTimestamp()
        ], now()->addMinutes($this->verificationCodeService->getExpiryMinutes()));

        if ($request->contact_type === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $request->contact, 'name' => $request->name], $code);
        } else {
            $this->smsService->sendVerificationCode($request->contact, $code);
        }

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Verification code sent',
            'next_step' => 2,
            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp(),
            'code_expires_at' => $this->verificationCodeService->getCodeExpiryTimestamp()
        ]);
    }

    public function multiStepStep2(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'code' => 'required|string|size:6'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1) {
            return response()->json(['error' => 'Invalid session'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (now()->timestamp > $session['code_expires_at']) {
            return response()->json(['error' => 'Verification code has expired'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($session['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid verification code'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Generate username suggestion
        $suggestedUsername = $this->generateUsername($session['name']);

        $session['verified'] = true;
        $session['step'] = 2;
        $session['suggested_username'] = $suggestedUsername;
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes($this->verificationCodeService->getExpiryMinutes()));

        return response()->json([
            'message' => 'Contact verified',
            'next_step' => 3,
            'suggested_username' => $suggestedUsername
        ]);
    }

    public function multiStepStep3(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'username' => ['nullable', new ValidUsername()],
            'password' => ['required', 'string', 'min:' . config('validation.password.min_length', 8), 'confirmed', new StrongPassword()]
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 2 || !$session['verified']) {
            return response()->json(['error' => 'Invalid session'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Use provided username or suggested username
        $username = $request->username ?? $session['suggested_username'];
        
        if (!$username) {
            return response()->json(['error' => 'Username is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $lockKey = "user_creation:{$session['contact']}";
        $lock = Cache::lock($lockKey, 10);
        
        if (!$lock->get()) {
            return response()->json(['error' => 'Registration in progress, please wait'], Response::HTTP_SERVICE_UNAVAILABLE);
        }
        
        try {
            if (User::where($session['contact_type'], $session['contact'])->exists()) {
                return response()->json(['error' => 'Unable to complete registration'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            if (User::where('username', $username)->exists()) {
                return response()->json(['error' => 'Username already taken'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create user with basic data first
            $userData = [
                'name' => $session['name'],
                'username' => $username,
                'date_of_birth' => $session['date_of_birth'],
                $session['contact_type'] => $session['contact'],
                'password' => 'temp', // Temporary password
            ];

            if ($session['contact_type'] === 'email') {
                $userData['email_verified_at'] = now();
            } else {
                $userData['phone_verified_at'] = now();
            }

            $user = User::create($userData);
            
            // Use PasswordSecurityService for consistent password handling
            try {
                app(\App\Services\PasswordSecurityService::class)->updatePassword($user, $request->password);
            } catch (\InvalidArgumentException $e) {
                // If password validation fails, delete the user and throw exception
                $user->delete();
                return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            if ($user->date_of_birth && $user->date_of_birth->age < 18) {
                $user->update(['is_child' => true]);
            }
            
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                // Continue without role
            }

            $token = app(\App\Services\SessionTimeoutService::class)->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
            Cache::forget("registration:{$request->session_id}");
            
            event(new UserRegistered($user));

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Registration completed'
            ], 201);
        } finally {
            $lock->release();
        }
    }

    public function multiStepResendCode(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|uuid']);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1) {
            return response()->json(['error' => 'Invalid session'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rateLimitResult = $this->rateLimiter->checkLimit('auth.resend', $session['contact']);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = $this->verificationCodeService->generateCode();
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = $this->verificationCodeService->getCodeExpiryTimestamp();
        $session['resend_count'] = ($session['resend_count'] ?? 0) + 1;
        
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes($this->verificationCodeService->getExpiryMinutes()));

        if ($session['contact_type'] === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $session['contact'], 'name' => $session['name']], $code);
        } else {
            $this->smsService->sendVerificationCode($session['contact'], $code);
        }

        return response()->json([
            'message' => 'New verification code sent',
            'session_id' => $request->session_id,
            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp(),
            'code_expires_at' => $this->verificationCodeService->getCodeExpiryTimestamp(),
            'attempts_remaining' => $rateLimitResult['remaining']
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)
                   ->whereNotNull('email_verification_token')
                   ->whereNull('email_verified_at')
                   ->first();
        
        if (!$user || !Hash::check($request->code, $user->email_verification_token)) {
            return response()->json(['error' => 'Invalid or expired code'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);
        
        // Upgrade role from 'user' to 'verified'
        if ($user->hasRole('user') && !$user->hasRole('verified')) {
            $user->removeRole('user');
            $user->assignRole('verified');
        }

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)
                   ->whereNull('email_verified_at')
                   ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or already verified'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rateLimitResult = $this->rateLimiter->checkLimit('email.resend', $request->email);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = $this->verificationCodeService->generateCode();
        $user->update(['email_verification_token' => Hash::make($code)]);

        $this->emailService->sendVerificationEmail($user, $code);

        return response()->json([
            'message' => 'Verification code sent',
            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp(),
            'attempts_remaining' => $rateLimitResult['remaining']
        ]);
    }

    public function emailVerificationStatus(Request $request): JsonResponse
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail()
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);
        
        $user = $request->user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect']
            ]);
        }
        
        $this->passwordService->updatePassword($user, $request->password);
        
        $auditService = app(\App\Services\AuditTrailService::class);
        $auditService->logSecurityEvent('password_changed', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ], $request);
        
        return response()->json([
            'message' => 'Password changed successfully',
            'password_strength' => $this->passwordService->getPasswordStrengthScore($request->password)
        ]);
    }

    public function enable2FA(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (!$this->twoFactorService->verifyPassword($user, $request->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password'],
            ]);
        }

        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled'], Response::HTTP_BAD_REQUEST);
        }

        $secret = $this->twoFactorService->generateSecret();
        $identifier = $user->email ?? $user->phone ?? $user->username;
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl(
            config('app.name'),
            $identifier,
            $secret
        );

        $user->update(['two_factor_secret' => encrypt($secret)]);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'message' => 'Scan QR code with Google Authenticator and verify',
        ]);
    }

    public function verify2FA(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();

        if (!$user->two_factor_secret) {
            return response()->json(['message' => '2FA not initialized'], Response::HTTP_BAD_REQUEST);
        }

        $secret = $this->twoFactorService->decryptSecret($user->two_factor_secret);
        $valid = $this->twoFactorService->verifyCode($secret, $request->code);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code'],
            ]);
        }

        $backupCodes = $this->twoFactorService->generateBackupCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => encrypt(json_encode($backupCodes['plain'])),
        ]);

        return response()->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes['plain'],
        ]);
    }

    public function disable2FA(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (!$this->twoFactorService->verifyPassword($user, $request->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password'],
            ]);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
        ]);
        
        $auditService = app(\App\Services\AuditTrailService::class);
        $auditService->logSecurityEvent('2fa_disabled', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ], $request);

        return response()->json(['message' => '2FA disabled successfully']);
    }

    public function phoneLoginSendCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/'
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['error' => 'Phone number not registered'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rateLimitResult = $this->rateLimiter->checkLimit('auth.phone_login', $request->phone);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = $this->verificationCodeService->generateCode();
        $sessionId = $this->verificationCodeService->generateSessionId();

        Cache::put("phone_login:{$sessionId}", [
            'phone' => $request->phone,
            'user_id' => $user->id,
            'code' => $code,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry())->timestamp
        ], now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry()));

        $this->smsService->sendLoginCode($request->phone, $code);

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Login code sent to your phone',
            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp(),
            'code_expires_at' => now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry())->timestamp
        ]);
    }

    public function phoneLoginVerifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'code' => 'required|string|size:6'
        ]);

        $session = Cache::get("phone_login:{$request->session_id}");
        if (!$session) {
            return response()->json(['error' => 'Invalid or expired session'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (now()->timestamp > $session['code_expires_at']) {
            return response()->json(['error' => 'Code has expired'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($session['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid code'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::find($session['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($user->two_factor_enabled) {
            $tempToken = app(\App\Services\SessionTimeoutService::class)->createTokenWithExpiry($user, 'temp_2fa')->plainTextToken;
            Cache::forget("phone_login:{$request->session_id}");
            return response()->json([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => 'Two-factor authentication required'
            ]);
        }

        $token = app(\App\Services\SessionTimeoutService::class)->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
        Cache::forget("phone_login:{$request->session_id}");

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function phoneLoginResendCode(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|uuid']);

        $session = Cache::get("phone_login:{$request->session_id}");
        if (!$session) {
            return response()->json(['error' => 'Invalid session'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rateLimitResult = $this->rateLimiter->checkLimit('auth.phone_resend', $session['phone']);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = $this->verificationCodeService->generateCode();
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry())->timestamp;
        $session['resend_count'] = ($session['resend_count'] ?? 0) + 1;
        
        Cache::put("phone_login:{$request->session_id}", $session, now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry()));
        $this->smsService->sendLoginCode($session['phone'], $code);

        return response()->json([
            'message' => 'New login code sent',
            'resend_available_at' => $this->verificationCodeService->getResendAvailableTimestamp(),
            'code_expires_at' => now()->addMinutes(app(\App\Services\SessionTimeoutService::class)->getTwoFactorCodeExpiry())->timestamp,
            'attempts_remaining' => $rateLimitResult['remaining']
        ]);
    }

    public function completeAgeVerification(Request $request): JsonResponse
    {
        $request->validate([
            'date_of_birth' => ['required', 'date', 'before:today', new MinimumAge()]
        ]);

        $user = $request->user();
        
        if ($user->date_of_birth) {
            return response()->json(['message' => 'Age already verified'], Response::HTTP_BAD_REQUEST);
        }

        $user->update(['date_of_birth' => $request->date_of_birth]);

        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $user->update(['is_child' => true]);
        }

        return response()->json([
            'message' => 'Age verification completed',
            'user' => $user
        ]);
    }

    public function getSecurityEvents(Request $request): JsonResponse
    {
        $auditService = app(\App\Services\AuditTrailService::class);
        $events = $auditService->getSecurityEvents(7);
        
        return response()->json($events);
    }

    public function checkUsernameAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', new ValidUsername()],
        ]);

        $available = !User::where('username', $request->username)->exists();

        return response()->json([
            'available' => $available,
            'username' => $request->username
        ]);
    }

    private function generateUsername(string $name): string
    {
        $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        
        if (empty($username) || is_numeric($username[0])) {
            $username = 'user' . $username;
        }
        
        $username = substr($username, 0, 15);
        
        if (strlen($username) < 4) {
            $username = $username . str_repeat('x', 4 - strlen($username));
        }
        
        $originalUsername = $username;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $suffix = (string)$count;
            $maxBase = 15 - strlen($suffix);
            $username = substr($originalUsername, 0, $maxBase) . $suffix;
            $count++;
        }
        
        return $username;
    }
}