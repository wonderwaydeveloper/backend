<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PhoneVerificationRequest;
use App\Http\Requests\PhoneLoginRequest;
use App\Http\Requests\PhoneRegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\PhoneVerificationCode;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PhoneAuthController extends Controller
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function sendCode(PhoneVerificationRequest $request)
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

    public function verifyCode(PhoneLoginRequest $request)
    {
        $validated = $request->validated();

        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('code', $validated['verification_code'])
            ->where('verified', false)
            ->latest()
            ->first();

        if (! $verification || $verification->isExpired()) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code'],
            ]);
        }

        $verification->update(['verified' => true]);

        return response()->json(['message' => 'Phone verified successfully', 'verified' => true]);
    }

    public function register(PhoneRegisterRequest $request)
    {
        $validated = $request->validated();

        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('verified', true)
            ->latest()
            ->first();

        if (! $verification) {
            throw ValidationException::withMessages([
                'phone' => ['Phone number not verified'],
            ]);
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

        $user->assignRole('user');
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => new UserResource($user), 'token' => $token], 201);
    }

    public function login(PhoneLoginRequest $request)
    {
        $validated = $request->validated();

        // For phone login, we need verification code, not password
        $verification = PhoneVerificationCode::where('phone', $validated['phone'])
            ->where('code', $validated['verification_code'])
            ->where('verified', true)
            ->latest()
            ->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('phone', $validated['phone'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }
}
