<?php

namespace App\Http\Controllers\Api;

use App\DTOs\{LoginDTO, UserRegistrationDTO};
use App\Http\Controllers\Controller;
use App\Http\Requests\{LoginRequest, RegisterRequest, PhoneVerificationRequest, PhoneLoginRequest, PhoneRegisterRequest};
use App\Models\{User, PhoneVerificationCode};
use App\Services\{AuthService, EmailService, SmsService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, Hash};
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class UnifiedAuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private EmailService $emailService,
        private SmsService $smsService
    ) {}

    // === Basic Authentication ===
    public function login(LoginRequest $request): JsonResponse
    {
        $loginDTO = LoginDTO::fromRequest($request->validated());
        $result = $this->authService->login($loginDTO);
        return response()->json($result);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $dto = new UserRegistrationDTO(
            name: $validated['name'],
            username: $validated['username'],
            email: $validated['email'],
            password: $validated['password'],
            dateOfBirth: $validated['date_of_birth']
        );
        
        $result = $this->authService->register($dto);
        return response()->json($result, 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Logout successful']);
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
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        $sessionId = Str::uuid();
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        if (User::where($request->contact_type, $request->contact)->exists()) {
            return response()->json(['error' => 'Contact already registered'], 422);
        }

        Cache::put("registration:{$sessionId}", [
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
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 2 || !$session['verified']) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        $userData = [
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'date_of_birth' => $request->date_of_birth,
            $session['contact_type'] => $session['contact']
        ];

        if ($session['contact_type'] === 'email') {
            $userData['email_verified_at'] = now();
        } else {
            $userData['phone_verified_at'] = now();
        }

        $user = User::create($userData);
        
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
            'url' => Socialite::driver($provider)->stateless()->redirect()->getTargetUrl()
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

        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('code', $validated['verification_code'])
            ->where('verified', true)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json(['error' => 'Invalid credentials'], 422);
        }

        $user = User::where('phone', $validated['phone'])->first();
        if (!$user) {
            return response()->json(['error' => 'Invalid credentials'], 422);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['user' => $user, 'token' => $token]);
    }

    // === Private Methods ===
    private function createOrUpdateSocialUser($socialUser, $provider): JsonResponse
    {
        $user = User::where('email', $socialUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'username' => $this->generateUsername($socialUser->getName()),
                'password' => Hash::make(uniqid()),
                'email_verified_at' => now(),
                'avatar' => $socialUser->getAvatar(),
            ]);
            
            try {
                $user->assignRole('user');
            } catch (\Exception $e) {
                // Continue without role
            }
        }

        $user->update(["{$provider}_id" => $socialUser->getId()]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    private function generateUsername($name): string
    {
        $username = str_replace(' ', '', strtolower($name));
        $count = User::where('username', 'like', $username . '%')->count();
        return $count > 0 ? $username . $count : $username;
    }
}