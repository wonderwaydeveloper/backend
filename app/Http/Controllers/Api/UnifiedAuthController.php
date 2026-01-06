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
        $result = $this->authService->login($loginDTO);
        return response()->json($result);
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
            'name' => 'required|string|max:255',
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
            'verified' => false
        ], now()->addMinutes(15));

        if ($request->contact_type === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $request->contact], $code);
        } else {
            $this->smsService->sendVerificationCode($request->contact, $code);
        }

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Verification code sent',
            'next_step' => 2
        ]);
    }

    public function multiStepStep2(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'code' => 'required|string|size:6'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1 || $session['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid session or code'], 422);
        }

        $session['verified'] = true;
        $session['step'] = 2;
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes(15));

        return response()->json(['message' => 'Contact verified', 'next_step' => 3]);
    }

    public function multiStepStep3(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
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

    // === Social Authentication ===
    public function socialRedirect(string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        return response()->json([
            'redirect_url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()
        ]);
    }

    public function socialCallback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['error' => 'Invalid provider'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            return $this->createOrUpdateSocialUser($socialUser, $provider);
        } catch (\Exception $e) {
            return response()->json(['error' => ucfirst($provider) . ' authentication failed'], 401);
        }
    }

    // === Phone Authentication ===
    public function phoneSendCode(PhoneVerificationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PhoneVerificationCode::create([
            'phone' => $validated['phone'],
            'code' => $code,
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->smsService->sendVerificationCode($validated['phone'], $code);
        return response()->json(['message' => 'Verification code sent successfully']);
    }

    public function phoneVerifyCode(PhoneLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('code', $validated['verification_code'])
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$verification || $verification->isExpired()) {
            return response()->json(['errors' => ['code' => ['Invalid or expired verification code']]], 422);
        }

        $verification->update(['verified' => true]);
        return response()->json(['message' => 'Phone verified successfully', 'verified' => true]);
    }

    public function phoneRegister(PhoneRegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('verified', true)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['errors' => ['phone' => ['Phone number not verified']]], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'phone_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'date_of_birth' => $validated['date_of_birth'],
        ]);

        // Check if user is under 18
        if ($user->date_of_birth && $user->date_of_birth->age < 18) {
            $user->update(['is_child' => true]);
        }

        try {
            $user->assignRole('user');
        } catch (\Exception $e) {
            // Continue without role
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token], 201);
    }

    public function phoneLogin(PhoneLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Find fresh verification code
        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('code', $validated['verification_code'])
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$verification || $verification->isExpired()) {
            return response()->json(['error' => 'Invalid or expired verification code'], 422);
        }

        $user = User::where('phone', $validated['phone'])->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 422);
        }

        // Mark as used and delete
        $verification->delete();

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token]);
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
        $username = str_replace(' ', '', strtolower($name));
        $count = User::where('username', 'like', $username . '%')->count();
        return $count > 0 ? $username . $count : $username;
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

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['email_verification_token' => Hash::make($code)]);

        $this->emailService->sendVerificationEmail($user, $code);

        return response()->json(['message' => 'Verification code sent']);
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
        
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Password reset code sent to your email']);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        $this->emailService->sendPasswordResetEmail($user, $code);
        
        return response()->json(['message' => 'Password reset code sent to your email']);
    }

    public function verifyResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData || !Hash::check($request->code, $tokenData->token)) {
            return response()->json(['valid' => false, 'message' => 'Invalid code'], 422);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 15) {
            return response()->json(['valid' => false, 'message' => 'Code expired'], 422);
        }

        return response()->json(['valid' => true, 'message' => 'Code is valid']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData || !Hash::check($request->code, $tokenData->token)) {
            return response()->json(['error' => 'Invalid code'], 422);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 15) {
            return response()->json(['error' => 'Code expired'], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

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

        return response()->json(['message' => '2FA disabled successfully']);
    }

    // === Age Verification for Social Auth ===
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
}