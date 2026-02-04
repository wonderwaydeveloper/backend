<?php

namespace App\Http\Controllers\Api;

use App\DTOs\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\{LoginRequest, PhoneVerificationRequest, PhoneLoginRequest, PhoneRegisterRequest};
use App\Models\{User, PhoneVerificationCode};
use App\Services\{AuthService, EmailService, SmsService, TwoFactorService, PasswordSecurityService, DeviceFingerprintService};
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
        
        // Rate limiting for login attempts
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("login_attempts:{$request->ip()}", 5, 15);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }
        
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
        
        // Check device verification with atomic operations
        if (isset($result['user'])) {
            $user = $result['user'];
            $fingerprint = DeviceFingerprintService::generate($request);
            
            // Use database lock to prevent race conditions
            $lockKey = "device_check:{$user->id}:{$fingerprint}";
            $lock = Cache::lock($lockKey, 10);
            
            if ($lock->get()) {
                try {
                    // Check if device is trusted
                    $trustedDevice = $user->devices()->where('fingerprint', $fingerprint)->where('is_trusted', true)->first();
                    
                    if (!$trustedDevice) {
                        // Create device verification session atomically
                        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                        
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
                            'expires_at' => now()->addMinutes(15)->timestamp,
                            'resend_count' => 0
                        ];
                        
                        $cacheKey = "device_verification_by_fingerprint:{$fingerprint}";
                        Cache::put($cacheKey, $verificationData, now()->addMinutes(15));
                        
                        // Send device verification email
                        $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
                        
                        return response()->json([
                            'requires_device_verification' => true,
                            'user_id' => $user->id,
                            'fingerprint' => $fingerprint,
                            'message' => 'Device verification required. Check your email for verification code.',
                            'code_expires_at' => $verificationData['expires_at'],
                            'resend_available_at' => now()->addSeconds(30)->timestamp
                        ]);
                    }
                } finally {
                    $lock->release();
                }
            } else {
                return response()->json(['error' => 'System busy, please try again'], 503);
            }
            
            // Log successful login
            $securityService = app(\App\Services\SecurityMonitoringService::class);
            $securityService->logSecurityEvent('authentication.success', [
                'user_id' => $result['user']->id,
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

        // Use atomic lock for user creation to prevent race conditions
        $lockKey = "user_creation:{$session['contact']}";
        $lock = Cache::lock($lockKey, 10);
        
        if (!$lock->get()) {
            return response()->json(['error' => 'Registration in progress, please wait'], 503);
        }
        
        try {
            // Double-check uniqueness within lock
            if (User::where($session['contact_type'], $session['contact'])->exists()) {
                return response()->json(['error' => 'Contact already registered'], 422);
            }
            
            if (User::where('username', $request->username)->exists()) {
                return response()->json(['error' => 'Username already taken'], 422);
            }

            $userData = [
                'name' => $session['name'],
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'password_changed_at' => now(),
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
        } finally {
            $lock->release();
        }
    }

    public function multiStepResendCode(Request $request): JsonResponse
    {
        $request->validate(['session_id' => 'required|uuid']);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $email = $session['contact'];
        $ip = $request->ip();
        
        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("resend:{$email}", 5, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = now()->addMinutes(15)->timestamp;
        $session['resend_count'] = ($session['resend_count'] ?? 0) + 1;
        
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes(15));

        if ($session['contact_type'] === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $session['contact'], 'name' => $session['name']], $code);
        } else {
            $this->smsService->sendVerificationCode($session['contact'], $code);
        }

        return response()->json([
            'message' => 'New verification code sent',
            'session_id' => $request->session_id,
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'code_expires_at' => now()->addMinutes(15)->timestamp,
            'attempts_remaining' => $rateLimitResult['remaining']
        ]);
    }

    // === Social Authentication ===
    public function socialRedirect(string $provider)
    {
        if (!in_array($provider, ['google'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function socialCallback(Request $request, string $provider)
    {
        if (!in_array($provider, ['google'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        try {
            $socialUser = $this->handleGoogleCallback($request->code);
            
            $user = User::where('email', $socialUser->email)->first();
            
            if (!$user) {
                $user = User::create([
                    'name' => $socialUser->name,
                    'email' => $socialUser->email,
                    'username' => $this->generateUsername($socialUser->name),
                    'password' => Hash::make(uniqid()),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'avatar' => $socialUser->avatar,
                    'date_of_birth' => null,
                ]);
                
                try {
                    $user->assignRole('user');
                } catch (\Exception $e) {
                    // Continue without role
                }
            }
            
            $user->update(['google_id' => $socialUser->id]);
            
            // Check device verification for social login
            $fingerprint = DeviceFingerprintService::generate($request);
            $trustedDevice = $user->devices()->where('fingerprint', $fingerprint)->where('is_trusted', true)->first();
            
            if (!$trustedDevice) {
                // Create device verification session for social login
                $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                
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
                    'expires_at' => now()->addMinutes(15)->timestamp,
                    'resend_count' => 0
                ];
                
                $cacheKey = "device_verification_by_fingerprint:{$fingerprint}";
                Cache::put($cacheKey, $verificationData, now()->addMinutes(15));
                
                // Send device verification email
                $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
                
                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                
                $queryParams = http_build_query([
                    'requires_device_verification' => 'true',
                    'user_id' => $user->id,
                    'fingerprint' => $fingerprint,
                    'requires_age_verification' => !$user->date_of_birth ? 'true' : 'false',
                    'provider' => $provider,
                    'message' => 'Device verification required. Check your email for verification code.',
                    'code_expires_at' => $verificationData['expires_at']
                ]);
                
                return redirect($frontendUrl . '/social/callback?' . $queryParams);
            }
            
            // Device is trusted, proceed with normal flow
            $token = $user->createToken('auth_token')->plainTextToken;
            
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            
            $queryParams = http_build_query([
                'token' => $token,
                'requires_age_verification' => !$user->date_of_birth,
                'provider' => $provider
            ]);
            
            return redirect($frontendUrl . '/social/callback?' . $queryParams);
        } catch (\Exception $e) {
            \Log::error('Social auth error: ' . $e->getMessage(), [
                'provider' => $provider,
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            
            $errorMessage = 'social_auth_failed';
            if (str_contains($e->getMessage(), 'timeout') || str_contains($e->getMessage(), 'Connection timed out')) {
                $errorMessage = 'connection_timeout';
            } elseif (str_contains($e->getMessage(), 'access_token')) {
                $errorMessage = 'token_exchange_failed';
            } elseif (str_contains($e->getMessage(), 'email')) {
                $errorMessage = 'email_not_received';
            }
            
            return redirect($frontendUrl . '/social/callback?error=' . $errorMessage);
        }
    }
    
    private function handleGoogleCallback($code)
    {
        $maxRetries = 2;
        $retryCount = 0;
        
        while ($retryCount <= $maxRetries) {
            try {
                $client = new \GuzzleHttp\Client([
                    'verify' => true,
                    'timeout' => 30,
                    'connect_timeout' => 10,
                    'curl' => [
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_MAXREDIRS => 3
                    ]
                ]);
                
                // Exchange code for token
                $response = $client->post('https://oauth2.googleapis.com/token', [
                    'form_params' => [
                        'client_id' => config('services.google.client_id'),
                        'client_secret' => config('services.google.client_secret'),
                        'redirect_uri' => config('services.google.redirect'),
                        'grant_type' => 'authorization_code',
                        'code' => $code
                    ]
                ]);
                
                $tokenData = json_decode($response->getBody(), true);
                
                if (!isset($tokenData['access_token'])) {
                    throw new \Exception('No access token received from Google');
                }
                
                // Get user info
                $userResponse = $client->get('https://www.googleapis.com/oauth2/v2/userinfo', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $tokenData['access_token']
                    ]
                ]);
                
                $userData = json_decode($userResponse->getBody(), true);
                
                if (!isset($userData['email'])) {
                    throw new \Exception('No email received from Google');
                }
                
                // Create user object compatible with Socialite
                return (object) [
                    'id' => $userData['id'],
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'avatar' => $userData['picture'] ?? null
                ];
                
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $retryCount++;
                if ($retryCount > $maxRetries) {
                    \Log::error('Google OAuth Connection Error after retries', [
                        'message' => $e->getMessage(),
                        'retries' => $retryCount
                    ]);
                    throw new \Exception('Connection to Google failed after multiple attempts');
                }
                \Log::warning('Google OAuth retry attempt', ['attempt' => $retryCount]);
                sleep(1); // Wait 1 second before retry
                continue;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                \Log::error('Google OAuth Request Error', [
                    'message' => $e->getMessage(),
                    'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null
                ]);
                throw new \Exception('Failed to authenticate with Google: ' . $e->getMessage());
            }
        }
    }

    // === Private Methods ===
    private function createOrUpdateSocialUser($socialUser, $provider): JsonResponse
    {
        // Handle both Socialite user object and manual user object
        $email = is_object($socialUser) && method_exists($socialUser, 'getEmail') 
            ? $socialUser->getEmail() 
            : $socialUser->email;
        $name = is_object($socialUser) && method_exists($socialUser, 'getName') 
            ? $socialUser->getName() 
            : $socialUser->name;
        $socialId = is_object($socialUser) && method_exists($socialUser, 'getId') 
            ? $socialUser->getId() 
            : $socialUser->id;
        $avatar = is_object($socialUser) && method_exists($socialUser, 'getAvatar') 
            ? $socialUser->getAvatar() 
            : ($socialUser->avatar ?? null);

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'username' => $this->generateUsername($name),
                'password' => Hash::make(uniqid()),
                'password_changed_at' => now(),
                'email_verified_at' => now(),
                'avatar' => $avatar,
                'date_of_birth' => null,
            ]);
            
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                // Continue without role
            }
            
            // Log social registration
            $securityService = app(\App\Services\SecurityMonitoringService::class);
            $securityService->logSecurityEvent('user.social_registration', [
                'user_id' => $user->id,
                'provider' => $provider,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]);
        }

        $user->update(["{$provider}_id" => $socialId]);
        
        // Device verification moved to DeviceController
        $token = $user->createToken('auth_token')->plainTextToken;
        
        // Log successful social login
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $securityService->logSecurityEvent('user.social_login_success', [
            'user_id' => $user->id,
            'provider' => $provider,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

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

        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("email_resend:{$request->email}", 3, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['email_verification_token' => Hash::make($code)]);

        $this->emailService->sendVerificationEmail($user, $code);

        return response()->json([
            'message' => 'Verification code sent',
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'attempts_remaining' => $rateLimitResult['remaining']
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
        
        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("forgot_password:{$request->email}", 2, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }
        
        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            Cache::put("password_reset:{$request->email}", [
                'email' => $request->email,
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
        
        $resetData = Cache::get("password_reset:{$request->email}");
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !$resetData) {
            return response()->json(['error' => 'Invalid reset request'], 422);
        }

        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("reset_resend:{$request->email}", 5, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        Cache::put("password_reset:{$request->email}", [
            'email' => $request->email,
            'code' => $code,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => now()->addMinutes(15)->timestamp,
            'resend_count' => ($resetData['resend_count'] ?? 0) + 1
        ], now()->addMinutes(15));

        $this->emailService->sendPasswordResetEmail($user, $code);
        
        return response()->json([
            'message' => 'New password reset code sent',
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'attempts_remaining' => $rateLimitResult['remaining']
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
        
        // Use PasswordSecurityService for secure password reset
        try {
            $this->passwordService->updatePassword($user, $request->password);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

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
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $securityService->logSecurityEvent('user.password_changed', [
            'user_id' => $user->id,
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
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $securityService->logSecurityEvent('user.2fa_disabled', [
            'user_id' => $user->id,
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

        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("phone_login:{$request->phone}", 5, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sessionId = Str::uuid();

        Cache::put("phone_login:{$sessionId}", [
            'phone' => $request->phone,
            'user_id' => $user->id,
            'code' => $code,
            'code_sent_at' => now()->timestamp,
            'code_expires_at' => now()->addMinutes(5)->timestamp
        ], now()->addMinutes(5));

        $this->smsService->sendLoginCode($request->phone, $code);

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
        $request->validate(['session_id' => 'required|uuid']);

        $session = Cache::get("phone_login:{$request->session_id}");
        if (!$session) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("phone_resend:{$session['phone']}", 5, 60);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $session['code'] = $code;
        $session['code_sent_at'] = now()->timestamp;
        $session['code_expires_at'] = now()->addMinutes(5)->timestamp;
        $session['resend_count'] = ($session['resend_count'] ?? 0) + 1;
        
        Cache::put("phone_login:{$request->session_id}", $session, now()->addMinutes(5));
        $this->smsService->sendLoginCode($session['phone'], $code);

        return response()->json([
            'message' => 'New login code sent',
            'resend_available_at' => now()->addSeconds(60)->timestamp,
            'code_expires_at' => now()->addMinutes(5)->timestamp,
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
    

    
    public function getSecurityEvents(Request $request): JsonResponse
    {
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $events = $securityService->getSecurityEvents($request->user()->id);
        
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