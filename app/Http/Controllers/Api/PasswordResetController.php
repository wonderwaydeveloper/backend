<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\{AuthService, RateLimitingService};
use App\Rules\StrongPassword;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Cache;

class PasswordResetController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private RateLimitingService $rateLimiter
    ) {}

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);
        
        $field = $request->contact_type;
        $success = $this->authService->forgotPassword($request->contact, $request, $field);
        
        return response()->json([
            'message' => 'If this ' . $field . ' is registered, a password reset code has been sent.',
            'resend_available_at' => now()->addSeconds(60)->timestamp
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone',
            'code' => 'required|string|size:6'
        ]);

        $cacheKey = "password_reset:{$request->contact}";
        $resetData = Cache::get($cacheKey);
        
        if (!$resetData || $resetData['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid or expired code'], 422);
        }
        
        if (now()->timestamp > $resetData['expires_at']) {
            return response()->json(['error' => 'Code has expired'], 422);
        }

        return response()->json(['message' => 'Code verified successfully']);
    }

    public function resendCode(Request $request): JsonResponse
    {
        $request->validate([
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);
        
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.password_resend', $request->contact);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }
        
        $field = $request->contact_type;
        $success = $this->authService->forgotPassword($request->contact, $request, $field);
        
        return response()->json([
            'message' => 'If this ' . $field . ' is registered, a new password reset code has been sent.',
            'resend_available_at' => now()->addSeconds(60)->timestamp
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone',
            'code' => 'required|string|size:6',
            'password' => ['required', 'string', 'min:8', 'confirmed', new StrongPassword()]
        ]);

        $success = $this->authService->resetPassword($request->code, $request->password, $request, $request->contact, $request->contact_type);
        
        if (!$success) {
            return response()->json(['error' => 'Invalid or expired code'], 422);
        }

        return response()->json(['message' => 'Password reset successfully']);
    }
}