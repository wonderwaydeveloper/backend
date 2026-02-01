<?php

namespace App\Http\Controllers\Api;

use App\DTOs\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\{LoginRequest, PhoneVerificationRequest, PhoneLoginRequest, PhoneRegisterRequest};
use App\Models\{User, PhoneVerificationCode};
use App\Services\{AuthService, EmailService, SmsService, TwoFactorService, PasswordSecurityService};
use App\Rules\{StrongPassword, MinimumAge};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Hash};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class UnifiedAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private EmailService $emailService,
        private SmsService $smsService,
        private TwoFactorService $twoFactorService,
        private PasswordSecurityService $passwordService
    ) {}

    // === Basic Authentication ===
    public function login(LoginRequest $request): JsonResponse
    {
        $loginDTO = LoginDTO::fromRequest($request->validated());
        
        // Check if 2FA code is provided
        if ($request->has('two_factor_code')) {
            return $this->verify2FALogin($loginDTO, $request->two_factor_code);
        }
        
        $result = $this->authService->login($loginDTO);
        
        // Check if user has 2FA enabled
        if (isset($result['user']) && $result['user']->two_factor_enabled) {
            return response()->json([
                'requires_2fa' => true,
                'message' => 'Two-factor authentication required'
            ]);
        }
        
        // Check device verification
        if (isset($result['user'])) {
            $deviceCheck = $this->checkDeviceVerification($request, $result['user']);
            if ($deviceCheck) {
                // Log suspicious login attempt
                $this->logSecurityEvent($result['user'], 'suspicious_login_attempt', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_fingerprint' => $this->generateFingerprint($request)
                ]);
                return response()->json($deviceCheck);
            }
            
            // Log successful login
            $this->logSecurityEvent($result['user'], 'successful_login', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
        }
        
        return response()->json($result);
    }
    
    private function verify2FALogin(LoginDTO $loginDTO, string $twoFactorCode): JsonResponse
    {
        // First verify credentials
        $result = $this->authService->login($loginDTO, false); // Don't create token yet
        
        if (!isset($result['user'])) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        
        $user = $result['user'];
        
        if (!$user->two_factor_enabled) {
            return response()->json(['error' => '2FA not enabled'], 400);
        }
        
        $secret = decrypt($user->two_factor_secret);
        $valid = $this->twoFactorService->verifyCode($secret, $twoFactorCode);
        
        if (!$valid) {
            return response()->json(['error' => 'Invalid 2FA code'], 422);
        }
        
        // Create token after successful 2FA
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful'
        ]);
    }
    
    private function checkDeviceVerification(Request $request, $user): ?array
    {
        $fingerprint = $this->generateFingerprint($request);
        
        // Check if device exists and is trusted
        $device = $user->devices()->where('fingerprint', $fingerprint)->first();
        
        if (!$device) {
            // Create new device record
            $device = $user->devices()->create([
                'token' => 'device_' . Str::random(40),
                'fingerprint' => $fingerprint,
                'device_name' => $this->getDeviceNameFromUserAgent($request->userAgent()),
                'device_type' => $this->getDeviceTypeFromUserAgent($request->userAgent()),
                'browser' => $this->getBrowserFromUserAgent($request->userAgent()),
                'os' => $this->getOSFromUserAgent($request->userAgent()),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_trusted' => false,
                'last_used_at' => now()
            ]);
        }
        
        if (!$device->is_trusted) {
            // Send verification email for new/untrusted device
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            Cache::put("device_verification:{$user->id}:{$fingerprint}", [
                'code' => $code,
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'device_info' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'location' => $this->getLocationFromIP($request->ip())
                ],
                'code_sent_at' => now()->timestamp,
                'expires_at' => now()->addMinutes(15)->timestamp
            ], now()->addMinutes(15));
            
            $this->emailService->sendDeviceVerificationEmail($user, $code, [
                'device_info' => $request->userAgent(),
                'location' => $this->getLocationFromIP($request->ip()),
                'ip' => $request->ip()
            ]);
            
            return [
                'requires_device_verification' => true,
                'message' => 'New device detected. Please check your email for verification code.',
                'fingerprint' => $fingerprint,
                'resend_available_at' => now()->addSeconds(60)->timestamp
            ];
        }
        
        // Update device last used
        $device->update(['last_used_at' => now()]);
        
        return null;
    }
    
    private function generateFingerprint(Request $request): string
    {
        // Use a more stable fingerprint that doesn't change between requests
        return hash('sha256', implode('|', [
            $request->userAgent() ?? '',
            $request->ip() ?? ''
        ]));
    }
    
    private function getLocationFromIP(string $ip): string
    {
        // Simplified location detection
        return 'Unknown Location';
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Logout successful']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Delete all tokens for this user
        $user->tokens()->delete();
        
        return response()->json(['message' => 'Logged out from all devices']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->getCurrentUser($request->user());
        return response()->json($user);
    }

    // === Multi-Step Registration ===
    public function multiStepStep1(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'date_of_birth' => ['required', 'date', 'before:today', new MinimumAge()],
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        $sessionId = Str::uuid();
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (User::where($request->contact_type, $request->contact)->exists()) {
            return response()->json(['error' => 'Contact already registered'], 422);
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
            'code_expires_at' => now()->addMinutes((int) config('auth_emails.verification.expire', 15))->timestamp
        ], now()->addMinutes(15));

        if ($request->contact_type === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $request->contact, 'name' => $request->name], $code);
        } else {
            $this->smsService->sendVerificationCode($request->contact, $code);
        }

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Verification code sent',
            'next_step' => 2,
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'code_expires_at' => now()->addMinutes((int) config('auth_emails.verification.expire', 15))->timestamp
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
            \Log::error('Step2 validation failed', [
                'session_id' => $request->session_id,
                'session_exists' => !!$session,
                'session_step' => $session['step'] ?? 'null'
            ]);
            return response()->json(['error' => 'Invalid session'], 422);
        }

        // Check if code is expired
        $codeExpiresAt = $session['code_expires_at'] ?? 0;
        if (now()->timestamp > $codeExpiresAt) {
            \Log::error('Code expired', [
                'session_id' => $request->session_id,
                'code_expires_at' => $codeExpiresAt,
                'current_time' => now()->timestamp,
                'expired_by' => now()->timestamp - $codeExpiresAt
            ]);
            return response()->json(['error' => 'Verification code has expired'], 422);
        }

        // Check if code matches
        if ($session['code'] !== $request->code) {
            \Log::error('Code mismatch', [
                'session_id' => $request->session_id,
                'expected_code' => $session['code'],
                'provided_code' => $request->code
            ]);
            return response()->json(['error' => 'Invalid verification code'], 422);
        }

        \Log::info('Code verified successfully', [
            'session_id' => $request->session_id,
            'code' => $request->code
        ]);

        $session['verified'] = true;
        $session['step'] = 2;
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes(15));

        return response()->json(['message' => 'Contact verified', 'next_step' => 3]);
    }

    public function multiStepStep3(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'username' => ['required', 'string', 'max:15', 'unique:users', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]{3,14}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword()]
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 2 || !$session['verified']) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $userData = [
            'name' => $session['name'],
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'date_of_birth' => $session['date_of_birth'],
            $session['contact_type'] => $session['contact']
        ];

        if ($session['contact_type'] === 'email') {
            $userData['email_verified_at'] = now();
        } else {
            $userData['phone_verified_at'] = now();
        }

        $user = User::create($userData);
        
        // Check if user is under 18
        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $user->update(['is_child' => true]);
        }
        
        try {
            $user->assignRole('user');
        } catch (\Exception $e) {
            // Continue without role if not exists
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        Cache::forget("registration:{$request->session_id}");

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration completed'
        ], 201);
    }

    public function multiStepResendCode(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $email = $session['contact'];
        $ip = $request->ip();
        
        // Check hourly limit (5 per hour)
        $hourlyKey = "resend_hourly:{$email}:" . now()->format('Y-m-d-H');
        $hourlyCount = Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests. Try again in ' . (60 - now()->minute) . ' minutes.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Check daily limit (10 per day)
        $dailyKey = "resend_daily:{$email}:" . now()->format('Y-m-d');
        $dailyCount = Cache::get($dailyKey, 0);
        if ($dailyCount >= 10) {
            return response()->json([
                'error' => 'Daily limit exceeded. Contact support if you need help.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }
        
        // Check IP daily limit (15 per day)
        $ipDailyKey = "resend_ip_daily:{$ip}:" . now()->format('Y-m-d');
        $ipDailyCount = Cache::get($ipDailyKey, 0);
        if ($ipDailyCount >= 15) {
            return response()->json([
                'error' => 'Too many requests from this location. Try again tomorrow.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }

        // Progressive delay after 3 attempts
        $attemptCount = $session['resend_count'] ?? 0;
        $baseDelay = 60;
        if ($attemptCount >= 3) {
            $baseDelay = min(300, 60 * pow(2, $attemptCount - 3)); // Max 5 minutes
        }

        // Check rate limiting
        $lastSentAt = $session['code_sent_at'] ?? 0;
        $timeSinceLastSent = now()->timestamp - $lastSentAt;
        
        if ($timeSinceLastSent < $baseDelay) {
            $remainingTime = $baseDelay - $timeSinceLastSent;
            return response()->json([
                'error' => 'Please wait before requesting another code',
                'remaining_seconds' => $remainingTime,
                'resend_available_at' => $lastSentAt + $baseDelay
            ], 429);
        }

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = now()->addMinutes((int) config('auth_emails.verification.expire', 15))->timestamp;
        $session['resend_count'] = $attemptCount + 1;
        
        // Update cache with new code and timestamp
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes(15));
        
        // Increment counters
        Cache::put($hourlyKey, $hourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($dailyKey, $dailyCount + 1, now()->addDay()->startOfDay());
        Cache::put($ipDailyKey, $ipDailyCount + 1, now()->addDay()->startOfDay());

        if ($session['contact_type'] === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $session['contact'], 'name' => $session['name']], $code);
        } else {
            $this->smsService->sendVerificationCode($session['contact'], $code);
        }

        $nextDelay = $attemptCount >= 2 ? min(300, 60 * pow(2, $attemptCount - 2)) : 60;
        
        return response()->json([
            'message' => 'New verification code sent',
            'session_id' => $request->session_id,
            'resend_available_at' => now()->addSeconds($nextDelay)->timestamp,
            'code_expires_at' => now()->addMinutes((int) config('auth_emails.verification.expire', 15))->timestamp,
            'attempts_remaining' => max(0, 5 - ($hourlyCount + 1))
        ]);
    }

    // === Social Authentication ===
    public function socialRedirect(string $provider): JsonResponse
    {
        if (!in_array($provider, ['google'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        return response()->json([
            'redirect_url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()
        ]);
    }

    public function socialCallback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            return $this->createOrUpdateSocialUser($socialUser, $provider);
        } catch (\Exception $e) {
            return response()->json(['error' => ucfirst($provider) . ' authentication failed'], 401);
        }
    }

    // === Private Methods ===
    private function createOrUpdateSocialUser($socialUser, $provider): JsonResponse
    {
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            // Check if we have date of birth info (usually we don't from social providers)
            // For social auth, we'll create user but mark as needing age verification
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'username' => $this->generateUsername($socialUser->getName()),
                'password' => Hash::make(uniqid()),
                'email_verified_at' => now(),
                'avatar' => $socialUser->getAvatar(),
                'date_of_birth' => null, // Will need to be filled later
            ]);
            
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                // Continue without role
            }
        }

        $user->update(["{$provider}_id" => $socialUser->getId()]);
        $token = $user->createToken('auth_token')->plainTextToken;

        // If user doesn't have date_of_birth, require it
        if (!$user->date_of_birth) {
            return response()->json([
                'user' => $user,
                'token' => $token,
                'requires_age_verification' => true,
                'message' => 'Please provide your date of birth to complete registration'
            ]);
        }

        return response()->json(['user' => $user, 'token' => $token]);
    }

    private function generateUsername($name): string
    {
        // Clean name: remove spaces, special chars, keep only alphanumeric
        $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        
        // Ensure it starts with letter (Twitter standard)
        if (empty($username) || is_numeric($username[0])) {
            $username = 'user' . $username;
        }
        
        // Limit to 15 characters max
        $username = substr($username, 0, 15);
        
        // Ensure minimum 4 characters
        if (strlen($username) < 4) {
            $username = $username . str_repeat('x', 4 - strlen($username));
        }
        
        // Check uniqueness and add number if needed
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

    // === Email Verification ===
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
            return response()->json(['error' => 'Invalid or expired code'], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)
                   ->whereNull('email_verified_at')
                   ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or already verified'], 422);
        }

        $email = $request->email;
        $ip = $request->ip();
        
        // Rate limiting per email (3 per hour)
        $emailHourlyKey = "email_resend_hourly:{$email}:" . now()->format('Y-m-d-H');
        $emailHourlyCount = Cache::get($emailHourlyKey, 0);
        if ($emailHourlyCount >= 3) {
            return response()->json([
                'error' => 'Too many requests for this email. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Rate limiting per IP (5 per hour)
        $ipHourlyKey = "email_resend_ip_hourly:{$ip}:" . now()->format('Y-m-d-H');
        $ipHourlyCount = Cache::get($ipHourlyKey, 0);
        if ($ipHourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests from this location. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Get attempt count from user record or cache
        $attemptKey = "email_resend_attempts:{$email}";
        $attemptCount = Cache::get($attemptKey, 0);
        
        // Progressive delay
        $baseDelay = 60;
        if ($attemptCount >= 3) {
            $baseDelay = min(300, 60 * pow(2, $attemptCount - 3));
        }
        
        // Check last sent time
        $lastSentKey = "email_resend_last:{$email}";
        $lastSentAt = Cache::get($lastSentKey, 0);
        $timeSinceLastSent = now()->timestamp - $lastSentAt;
        
        if ($timeSinceLastSent < $baseDelay) {
            $remainingTime = $baseDelay - $timeSinceLastSent;
            return response()->json([
                'error' => 'Please wait before requesting another code',
                'remaining_seconds' => $remainingTime,
                'resend_available_at' => $lastSentAt + $baseDelay
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['email_verification_token' => Hash::make($code)]);

        $this->emailService->sendVerificationEmail($user, $code);
        
        // Update counters and timestamps
        Cache::put($emailHourlyKey, $emailHourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($ipHourlyKey, $ipHourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($attemptKey, $attemptCount + 1, now()->addDay());
        Cache::put($lastSentKey, now()->timestamp, now()->addDay());
        
        $nextDelay = $attemptCount >= 2 ? min(300, 60 * pow(2, $attemptCount - 2)) : 60;

        return response()->json([
            'message' => 'Verification code sent',
            'resend_available_at' => now()->addSeconds($nextDelay)->timestamp,
            'attempts_remaining' => max(0, 3 - ($emailHourlyCount + 1))
        ]);
    }

    public function emailVerificationStatus(Request $request): JsonResponse
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail()
        ]);
    }

    // === Password Reset ===
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        
        $email = $request->email;
        $ip = $request->ip();
        
        // Rate limiting per IP (3 attempts per hour)
        $ipHourlyKey = "forgot_ip_hourly:{$ip}:" . now()->format('Y-m-d-H');
        $ipHourlyCount = Cache::get($ipHourlyKey, 0);
        if ($ipHourlyCount >= 3) {
            return response()->json([
                'error' => 'Too many password reset attempts. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Rate limiting per email (2 attempts per hour)
        $emailHourlyKey = "forgot_email_hourly:{$email}:" . now()->format('Y-m-d-H');
        $emailHourlyCount = Cache::get($emailHourlyKey, 0);
        if ($emailHourlyCount >= 2) {
            return response()->json([
                'error' => 'Too many requests for this email. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        $user = User::where('email', $email)->first();
        
        // Always increment counters to prevent enumeration
        Cache::put($ipHourlyKey, $ipHourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($emailHourlyKey, $emailHourlyCount + 1, now()->addHour()->startOfHour());
        
        // Log suspicious activity
        if (!$user) {
            \Log::warning('Password reset attempt for non-existent email', [
                'email' => $email,
                'ip' => $ip,
                'user_agent' => $request->userAgent()
            ]);
        }
        
        // Always return success message to prevent enumeration
        if ($user) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            Cache::put("password_reset:{$email}", [
                'email' => $email,
                'code' => $code,
                'code_sent_at' => now()->timestamp,
                'code_expires_at' => now()->addMinutes(15)->timestamp
            ], now()->addMinutes(15));

            $this->emailService->sendPasswordResetEmail($user, $code);
        }
        
        return response()->json([
            'message' => 'If this email is registered, a password reset code has been sent.',
            'resend_available_at' => now()->addSeconds(60)->timestamp
        ]);
    }

    public function resendResetCode(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        
        $email = $request->email;
        $resetData = Cache::get("password_reset:{$email}");
        
        // Check if user exists first
        $user = User::where('email', $email)->first();
        if (!$user || !$resetData) {
            return response()->json(['error' => 'Invalid reset request'], 422);
        }

        $email = $request->email;
        $ip = $request->ip();
        
        // Check hourly limit (5 per hour)
        $hourlyKey = "reset_hourly:{$email}:" . now()->format('Y-m-d-H');
        $hourlyCount = Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests. Try again in ' . (60 - now()->minute) . ' minutes.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Check daily limit (10 per day)
        $dailyKey = "reset_daily:{$email}:" . now()->format('Y-m-d');
        $dailyCount = Cache::get($dailyKey, 0);
        if ($dailyCount >= 10) {
            return response()->json([
                'error' => 'Daily limit exceeded. Contact support if you need help.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }
        
        // Check IP daily limit (15 per day)
        $ipDailyKey = "reset_ip_daily:{$ip}:" . now()->format('Y-m-d');
        $ipDailyCount = Cache::get($ipDailyKey, 0);
        if ($ipDailyCount >= 15) {
            return response()->json([
                'error' => 'Too many requests from this location. Try again tomorrow.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }

        // Progressive delay after 3 attempts
        $attemptCount = $resetData['resend_count'] ?? 0;
        $baseDelay = 60;
        if ($attemptCount >= 3) {
            $baseDelay = min(300, 60 * pow(2, $attemptCount - 3)); // Max 5 minutes
        }

        // Check rate limiting
        $lastSentAt = $resetData['code_sent_at'] ?? 0;
        $timeSinceLastSent = now()->timestamp - $lastSentAt;
        
        if ($timeSinceLastSent < $baseDelay) {
            $remainingTime = $baseDelay - $timeSinceLastSent;
            return response()->json([
                'error' => 'Please wait before requesting another code',
                'remaining_seconds' => $remainingTime,
                'resend_available_at' => $lastSentAt + $baseDelay
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        Cache::put("password_reset:{$request->email}", [
            'email' => $request->email,
            'code' => $code,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => now()->addMinutes(15)->timestamp,
            'resend_count' => $attemptCount + 1
        ], now()->addMinutes(15));
        
        // Increment counters
        Cache::put($hourlyKey, $hourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($dailyKey, $dailyCount + 1, now()->addDay()->startOfDay());
        Cache::put($ipDailyKey, $ipDailyCount + 1, now()->addDay()->startOfDay());

        $this->emailService->sendPasswordResetEmail($user, $code);
        
        $nextDelay = $attemptCount >= 2 ? min(300, 60 * pow(2, $attemptCount - 2)) : 60;
        
        return response()->json([
            'message' => 'New password reset code sent',
            'resend_available_at' => now()->addSeconds($nextDelay)->timestamp,
            'attempts_remaining' => max(0, 5 - ($hourlyCount + 1))
        ]);
    }

    public function verifyResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $resetData = Cache::get("password_reset:{$request->email}");
        if (!$resetData || $resetData['code'] !== $request->code) {
            return response()->json(['valid' => false, 'message' => 'Invalid code'], 422);
        }

        if (now()->timestamp > $resetData['code_expires_at']) {
            return response()->json(['valid' => false, 'message' => 'Code expired'], 422);
        }

        return response()->json(['valid' => true, 'message' => 'Code is valid']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword()]
        ]);

        $resetData = Cache::get("password_reset:{$request->email}");
        if (!$resetData || $resetData['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid code'], 422);
        }

        if (now()->timestamp > $resetData['code_expires_at']) {
            return response()->json(['error' => 'Code expired'], 422);
        }

        $user = User::where('email', $request->email)->first();
        
        // Check if new password is same as current password
        if (Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'New password must be different from current password'], 422);
        }
        
        $user->update(['password' => Hash::make($request->password)]);

        Cache::forget("password_reset:{$request->email}");

        return response()->json(['message' => 'Password reset successfully']);
    }

    // === Password Management ===
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
        
        // Log password change event
        $this->logSecurityEvent($user, 'password_changed', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
        
        return response()->json([
            'message' => 'Password changed successfully',
            'password_strength' => $this->passwordService->getPasswordStrengthScore($request->password)
        ]);
    }

    // === Two Factor Authentication ===
    public function enable2FA(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password'],
            ]);
        }

        if ($user->two_factor_enabled) {
            return response()->json(['message' => '2FA is already enabled'], 400);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->getQRCodeUrl(
            config('app.name'),
            $user->email,
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
            return response()->json(['message' => '2FA not initialized'], 400);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid = $this->twoFactorService->verifyCode($secret, $request->code);

        if (!$valid) {
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code'],
            ]);
        }

        $backupCodes = $this->twoFactorService->generateBackupCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => encrypt(json_encode($backupCodes)),
        ]);

        return response()->json([
            'message' => '2FA enabled successfully',
            'backup_codes' => $backupCodes,
        ]);
    }

    public function disable2FA(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Incorrect password'],
            ]);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
        ]);
        
        // Log 2FA disable event
        $this->logSecurityEvent($user, '2fa_disabled', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['message' => '2FA disabled successfully']);
    }

    // === Phone Login with OTP ===
    public function phoneLoginSendCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|regex:/^09[0-9]{9}$/'
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['error' => 'Phone number not registered'], 422);
        }

        $phone = $request->phone;
        $ip = $request->ip();
        
        // Rate limiting
        $hourlyKey = "phone_login_hourly:{$phone}:" . now()->format('Y-m-d-H');
        $hourlyCount = Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests. Try again in ' . (60 - now()->minute) . ' minutes.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sessionId = Str::uuid();

        Cache::put("phone_login:{$sessionId}", [
            'phone' => $phone,
            'user_id' => $user->id,
            'code' => $code,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => now()->addMinutes(5)->timestamp
        ], now()->addMinutes(5));

        $this->smsService->sendLoginCode($phone, $code);
        
        Cache::put($hourlyKey, $hourlyCount + 1, now()->addHour()->startOfHour());

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Login code sent to your phone',
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'code_expires_at' => now()->addMinutes(5)->timestamp
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
            return response()->json(['error' => 'Invalid or expired session'], 422);
        }

        if (now()->timestamp > $session['code_expires_at']) {
            return response()->json(['error' => 'Code has expired'], 422);
        }

        if ($session['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid code'], 422);
        }

        $user = User::find($session['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 422);
        }

        // Check 2FA requirement
        if ($user->two_factor_enabled) {
            $tempToken = $user->createToken('temp_2fa')->plainTextToken;
            Cache::forget("phone_login:{$request->session_id}");
            return response()->json([
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => 'Two-factor authentication required'
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        Cache::forget("phone_login:{$request->session_id}");

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function phoneLoginResendCode(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid'
        ]);

        $session = Cache::get("phone_login:{$request->session_id}");
        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $phone = $session['phone'];
        $ip = $request->ip();
        
        // Rate limiting
        $hourlyKey = "phone_login_hourly:{$phone}:" . now()->format('Y-m-d-H');
        $hourlyCount = Cache::get($hourlyKey, 0);
        if ($hourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests. Try again in ' . (60 - now()->minute) . ' minutes.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }

        // Check daily limit (10 per day)
        $dailyKey = "phone_login_daily:{$phone}:" . now()->format('Y-m-d');
        $dailyCount = Cache::get($dailyKey, 0);
        if ($dailyCount >= 10) {
            return response()->json([
                'error' => 'Daily limit exceeded. Contact support if you need help.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }
        
        // Check IP daily limit (15 per day)
        $ipDailyKey = "phone_login_ip_daily:{$ip}:" . now()->format('Y-m-d');
        $ipDailyCount = Cache::get($ipDailyKey, 0);
        if ($ipDailyCount >= 15) {
            return response()->json([
                'error' => 'Too many requests from this location. Try again tomorrow.',
                'retry_after' => now()->addDay()->startOfDay()->timestamp
            ], 429);
        }

        // Progressive delay after 3 attempts
        $attemptCount = $session['resend_count'] ?? 0;
        $baseDelay = 60;
        if ($attemptCount >= 3) {
            $baseDelay = min(300, 60 * pow(2, $attemptCount - 3)); // Max 5 minutes
        }
        
        // Check resend timing
        $lastSentAt = $session['code_sent_at'] ?? 0;
        $timeSinceLastSent = now()->timestamp - $lastSentAt;
        
        if ($timeSinceLastSent < $baseDelay) {
            $remainingTime = $baseDelay - $timeSinceLastSent;
            return response()->json([
                'error' => 'Please wait before requesting another code',
                'remaining_seconds' => $remainingTime,
                'resend_available_at' => $lastSentAt + $baseDelay
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = now()->addMinutes(5)->timestamp;
        $session['resend_count'] = $attemptCount + 1;
        
        Cache::put("phone_login:{$request->session_id}", $session, now()->addMinutes(5));
        Cache::put($hourlyKey, $hourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($dailyKey, $dailyCount + 1, now()->addDay()->startOfDay());
        Cache::put($ipDailyKey, $ipDailyCount + 1, now()->addDay()->startOfDay());

        $this->smsService->sendLoginCode($phone, $code);
        
        $nextDelay = $attemptCount >= 2 ? min(300, 60 * pow(2, $attemptCount - 2)) : 60;

        return response()->json([
            'message' => 'New login code sent',
            'resend_available_at' => now()->addSeconds($nextDelay)->timestamp,
            'code_expires_at' => now()->addMinutes(5)->timestamp,
            'attempts_remaining' => max(0, 5 - ($hourlyCount + 1))
        ]);
    }
    public function completeAgeVerification(Request $request): JsonResponse
    {
        $request->validate([
            'date_of_birth' => ['required', 'date', 'before:today', new MinimumAge()]
        ]);

        $user = $request->user();
        
        if ($user->date_of_birth) {
            return response()->json(['message' => 'Age already verified'], 400);
        }

        $user->update(['date_of_birth' => $request->date_of_birth]);

        // Check if user is under 18
        if ($user->date_of_birth && $user->date_of_birth->age < config('age_restrictions.child_age_threshold', 18)) {
            $user->update(['is_child' => true]);
        }

        return response()->json([
            'message' => 'Age verification completed',
            'user' => $user
        ]);
    }
    
    public function resendDeviceCode(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint' => 'required|string'
        ]);
        
        // Try to find verification data by fingerprint
        $verificationData = null;
        $cacheKey = null;
        
        // Try different possible cache key patterns
        $possibleKeys = [
            "device_verification:*:{$request->fingerprint}",
        ];
        
        // Get all users and check their device verification cache
        $users = User::all();
        foreach ($users as $user) {
            $key = "device_verification:{$user->id}:{$request->fingerprint}";
            $data = Cache::get($key);
            if ($data) {
                $verificationData = $data;
                $cacheKey = $key;
                break;
            }
        }
        
        if (!$verificationData) {
            return response()->json(['error' => 'No verification session found'], 422);
        }
        
        // Get user
        $user = User::find($verificationData['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 422);
        }
        
        $email = $user->email;
        $ip = $request->ip();
        
        // Rate limiting per email (3 per hour)
        $emailHourlyKey = "device_resend_email_hourly:{$email}:" . now()->format('Y-m-d-H');
        $emailHourlyCount = Cache::get($emailHourlyKey, 0);
        if ($emailHourlyCount >= 3) {
            return response()->json([
                'error' => 'Too many requests for this email. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Rate limiting per IP (5 per hour)
        $ipHourlyKey = "device_resend_ip_hourly:{$ip}:" . now()->format('Y-m-d-H');
        $ipHourlyCount = Cache::get($ipHourlyKey, 0);
        if ($ipHourlyCount >= 5) {
            return response()->json([
                'error' => 'Too many requests from this location. Try again later.',
                'retry_after' => now()->addHour()->startOfHour()->timestamp
            ], 429);
        }
        
        // Progressive delay after 3 attempts
        $attemptCount = $verificationData['resend_count'] ?? 0;
        $baseDelay = 60;
        if ($attemptCount >= 3) {
            $baseDelay = min(300, 60 * pow(2, $attemptCount - 3)); // Max 5 minutes
        }
        
        // Check resend timing
        $lastSentAt = $verificationData['code_sent_at'] ?? 0;
        $timeSinceLastSent = now()->timestamp - $lastSentAt;
        
        if ($timeSinceLastSent < $baseDelay) {
            $remainingTime = $baseDelay - $timeSinceLastSent;
            return response()->json([
                'error' => 'Please wait before requesting another code',
                'remaining_seconds' => $remainingTime,
                'resend_available_at' => $lastSentAt + $baseDelay
            ], 429);
        }
        
        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Update cache with new code and timestamp
        $verificationData['code'] = $code;
        $verificationData['code_sent_at'] = now()->timestamp;
        $verificationData['expires_at'] = now()->addMinutes(15)->timestamp;
        $verificationData['resend_count'] = $attemptCount + 1;
        
        Cache::put($cacheKey, $verificationData, now()->addMinutes(15));
        
        // Increment rate limiting counters
        Cache::put($emailHourlyKey, $emailHourlyCount + 1, now()->addHour()->startOfHour());
        Cache::put($ipHourlyKey, $ipHourlyCount + 1, now()->addHour()->startOfHour());
        
        // Send new verification email
        $this->emailService->sendDeviceVerificationEmail($user, $code, [
            'device_info' => $verificationData['device_info']['user_agent'] ?? 'Unknown Device',
            'location' => $verificationData['device_info']['location'] ?? 'Unknown Location',
            'ip' => $verificationData['device_info']['ip'] ?? $request->ip()
        ]);
        
        $nextDelay = $attemptCount >= 2 ? min(300, 60 * pow(2, $attemptCount - 2)) : 60;
        
        return response()->json([
            'message' => 'New verification code sent to your email',
            'code_expires_at' => $verificationData['expires_at'],
            'resend_available_at' => now()->addSeconds($nextDelay)->timestamp,
            'attempts_remaining' => max(0, 3 - ($emailHourlyCount + 1))
        ]);
    }

    public function verifyDevice(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'fingerprint' => 'required|string'
        ]);
        
        // Try to find verification data by fingerprint
        $verificationData = null;
        $cacheKey = null;
        
        // Get all users and check their device verification cache
        $users = User::all();
        foreach ($users as $user) {
            $key = "device_verification:{$user->id}:{$request->fingerprint}";
            $data = Cache::get($key);
            if ($data) {
                $verificationData = $data;
                $cacheKey = $key;
                break;
            }
        }
        
        if (!$verificationData || $verificationData['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid verification code'], 422);
        }
        
        if (now()->timestamp > $verificationData['expires_at']) {
            return response()->json(['error' => 'Verification code expired'], 422);
        }
        
        // Get user from verification data
        $user = User::find($verificationData['user_id']);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 422);
        }
        
        // Create or update device as trusted
        $device = $user->devices()->where('fingerprint', $request->fingerprint)->first();
        
        if (!$device) {
            $device = $user->devices()->create([
                'token' => 'device_' . Str::random(40),
                'fingerprint' => $request->fingerprint,
                'device_name' => $this->getDeviceNameFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                'device_type' => $this->getDeviceTypeFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                'browser' => $this->getBrowserFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                'os' => $this->getOSFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                'ip_address' => $verificationData['device_info']['ip'] ?? $request->ip(),
                'user_agent' => $verificationData['device_info']['user_agent'] ?? 'Unknown',
                'is_trusted' => true,
                'last_used_at' => now()
            ]);
        } else {
            $device->update(['is_trusted' => true, 'last_used_at' => now()]);
        }
        
        // Clear verification cache
        Cache::forget($cacheKey);
        
        // Create auth token
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Device verified and login successful'
        ]);
    }
    
    private function getDeviceNameFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'Mobile Device';
        if (str_contains($userAgent, 'Tablet')) return 'Tablet';
        return 'Desktop';
    }
    
    private function getDeviceTypeFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'android';
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) return 'ios';
        return 'web';
    }
    
    private function getBrowserFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        return 'Unknown';
    }
    
    private function getOSFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Mac')) return 'macOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iOS')) return 'iOS';
        return 'Unknown';
    }
    
    private function logSecurityEvent($user, string $eventType, array $data = []): void
    {
        \DB::table('security_logs')->insert([
            'user_id' => $user->id,
            'event_type' => $eventType,
            'ip_address' => $data['ip'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'metadata' => json_encode($data),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Send notification for critical events
        if (in_array($eventType, ['suspicious_login_attempt', 'password_changed', '2fa_disabled'])) {
            $this->sendSecurityNotification($user, $eventType, $data);
        }
    }
    
    public function getSecurityEvents(Request $request): JsonResponse
    {
        $events = \DB::table('security_logs')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json($events);
    }
    
    private function sendSecurityNotification($user, string $eventType, array $data): void
    {
        $messages = [
            'suspicious_login_attempt' => 'New device login detected',
            'password_changed' => 'Your password was changed',
            '2fa_disabled' => 'Two-factor authentication was disabled'
        ];
        
        $this->emailService->sendSecurityAlert($user, [
            'event' => $eventType,
            'message' => $messages[$eventType] ?? 'Security event detected',
            'data' => $data,
            'timestamp' => now()->toDateTimeString()
        ]);
    }
}