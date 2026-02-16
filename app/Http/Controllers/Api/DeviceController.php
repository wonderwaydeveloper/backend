<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, DeviceToken};
use App\Services\{DeviceFingerprintService, EmailService};
use App\Http\Requests\{RegisterDeviceRequest, AdvancedDeviceRequest, TrustDeviceRequest};
use App\Http\Resources\DeviceResource;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Cache, Hash, DB};
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DeviceController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private \App\Services\RateLimitingService $rateLimiter,
        private \App\Services\SessionTimeoutService $timeoutService,
        private \App\Services\VerificationCodeService $verificationCodeService
    ) {
        $this->middleware('auth:sanctum');
    }
    /**
     * Register a new device (simple registration)
     */
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $this->authorize('register', DeviceToken::class);

        $fingerprint = DeviceFingerprintService::generate($request);

        $device = DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'fingerprint' => $fingerprint,
            ],
            [
                'token' => $request->input('token'),
                'device_type' => $request->input('platform'),
                'device_name' => $request->input('device_name', 'Unknown Device'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
                'is_trusted' => false
            ]
        );

        return response()->json([
            'device' => new DeviceResource($device),
            'requires_verification' => !$device->is_trusted,
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * Advanced device registration with detailed info
     */
    public function registerAdvanced(AdvancedDeviceRequest $request): JsonResponse
    {
        $this->authorize('register', DeviceToken::class);

        $fingerprint = DeviceFingerprintService::generate($request);
        
        // Use updateOrCreate to prevent race conditions
        $device = DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'fingerprint' => $fingerprint,
            ],
            [
                'token' => 'device_' . Str::random(config('authentication.device.token_length')),
                'device_name' => $request->name,
                'device_type' => $request->type,
                'browser' => $request->browser,
                'os' => $request->os,
                'push_token' => $request->push_token,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now(),
                'is_trusted' => false
            ]
        );

        return response()->json([
            'device' => new DeviceResource($device),
            'requires_verification' => !$device->is_trusted
        ]);
    }

    /**
     * List user devices with current device detection
     */
    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', DeviceToken::class);
        $currentFingerprint = DeviceFingerprintService::generate($request);
        
        $devices = $request->user()->devices()
            ->select(['id', 'device_name', 'device_type', 'browser', 'os', 'ip_address', 'last_used_at', 'is_trusted', 'created_at', 'fingerprint'])
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($device) use ($currentFingerprint) {
                $device->is_current = $device->fingerprint === $currentFingerprint;
                unset($device->fingerprint); // Remove for security
                return $device;
            });

        return response()->json(DeviceResource::collection($devices));
    }

    /**
     * Trust a device
     */
    public function trust(TrustDeviceRequest $request, $deviceId): JsonResponse
    {
        $device = $request->user()->devices()->findOrFail($deviceId);
        $this->authorize('trust', $device);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $device->update(['is_trusted' => true]);

        return response()->json(['message' => 'Device trusted successfully']);
    }

    /**
     * Revoke a single device with security checks
     */
    public function revoke(Request $request, $deviceId): JsonResponse
    {
        $this->authorize('revoke', DeviceToken::class);
        $device = $request->user()->devices()->findOrFail($deviceId);
        $currentFingerprint = DeviceFingerprintService::generate($request);
        
        // Prevent revoking current device
        if ($device->fingerprint === $currentFingerprint) {
            return response()->json(['error' => 'Cannot revoke current device'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        
        // Use transaction to prevent race conditions
        DB::transaction(function () use ($user, $device, $currentToken) {
            // Revoke tokens for security
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();
            
            $device->delete();
            
            // Clear cached data
            Cache::forget("device_verification_by_fingerprint:{$device->fingerprint}");
        });

        return response()->json([
            'message' => 'Device revoked successfully',
            'warning' => 'Other sessions terminated for security'
        ]);
    }

    /**
     * Revoke all devices except current
     */
    public function revokeAll(Request $request): JsonResponse
    {
        $this->authorize('revoke', DeviceToken::class);
        
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $currentFingerprint = DeviceFingerprintService::generate($request);
        
        // Use transaction to prevent race conditions
        DB::transaction(function () use ($user, $currentToken, $currentFingerprint) {
            // Revoke all other tokens
            $user->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();

            // Remove all other devices
            $user->devices()
                ->where('fingerprint', '!=', $currentFingerprint)
                ->delete();
        });

        return response()->json(['message' => 'All other devices revoked successfully']);
    }

    /**
     * Verify device for authentication
     */
    public function verifyDevice(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'fingerprint' => 'required|string'
        ]);
        
        $ip = $request->ip();
        $fingerprint = $request->fingerprint;
        
        $rateLimitResult = $this->rateLimiter->checkLimit('device.verify', "{$ip}:{$fingerprint}");
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after'],
                'errors' => ['code' => ['Too many attempts. Please wait before trying again.']]
            ], 429);
        }
            
            // Get verification session from cache using fingerprint only
            $cacheKey = "device_verification_by_fingerprint:{$fingerprint}";
            $verificationData = Cache::get($cacheKey);
            
            if (!$verificationData || $verificationData['code'] !== $request->code) {
                \Log::warning('Device verification failed - invalid code', [
                    'fingerprint' => $fingerprint,
                    'ip' => $ip
                ]);
                return response()->json([
                    'error' => 'Invalid verification code or session expired',
                    'errors' => ['code' => ['Invalid or expired verification code']]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            if (now()->timestamp > $verificationData['expires_at']) {
                Cache::forget($cacheKey);
                return response()->json([
                    'error' => 'Verification code expired',
                    'errors' => ['code' => ['Verification code has expired. Please request a new one.']]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $user = User::find($verificationData['user_id']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // Create or update device as trusted with atomic operations
            $deviceLockKey = "device_creation:{$user->id}:{$fingerprint}";
            $deviceLock = Cache::lock($deviceLockKey, 5);
            
            if (!$deviceLock->get()) {
                return response()->json(['error' => 'Device creation in progress'], Response::HTTP_SERVICE_UNAVAILABLE);
            }
            
            try {
                $device = $user->devices()->updateOrCreate(
                    ['fingerprint' => $fingerprint],
                    [
                        'token' => 'device_' . Str::random(config('authentication.device.token_length')),
                        'device_name' => $this->getDeviceNameFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                        'device_type' => $this->getDeviceTypeFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                        'browser' => $this->getBrowserFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                        'os' => $this->getOSFromUserAgent($verificationData['device_info']['user_agent'] ?? 'Unknown'),
                        'ip_address' => $verificationData['device_info']['ip'] ?? $request->ip(),
                        'user_agent' => $verificationData['device_info']['user_agent'] ?? 'Unknown',
                        'is_trusted' => true,
                        'last_used_at' => now()
                    ]
                );
            } finally {
                $deviceLock->release();
            }
            
            Cache::forget($cacheKey);
            
            $token = app(\App\Services\SessionTimeoutService::class)->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
            
            \Log::info('Device verification successful', [
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'device_id' => $device->id
            ]);
            
            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Device verified and login successful'
            ]);
    }

    /**
     * Resend device verification code
     */
    public function resendDeviceCode(Request $request): JsonResponse
    {
        $request->validate([
            'fingerprint' => 'required|string',
            'user_id' => 'nullable|integer'
        ]);
        
        $fingerprint = $request->fingerprint;
        $userId = $request->user_id;
        
        $rateLimitResult = $this->rateLimiter->checkLimit('device.resend', "{$request->ip()}:{$fingerprint}");
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }
        // Get verification session from cache using fingerprint only
        $sessionKey = "device_verification_by_fingerprint:{$fingerprint}";
        $sessionData = Cache::get($sessionKey);
        
        if (!$sessionData) {
            return response()->json([
                'error' => 'No verification session found. Please try logging in again.',
                'errors' => ['session' => ['Verification session expired or not found']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        $userId = $sessionData['user_id'];
        
        // Find user
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'errors' => ['user_id' => ['Invalid user ID']]
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        
        // Update existing session data
        $existingData = $sessionData;
        
        $code = $this->verificationCodeService->generateCode();
        
        $verificationData = [
            'code' => $code,
            'user_id' => $userId,
            'fingerprint' => $fingerprint,
            'device_info' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'location' => 'Unknown Location'
            ],
            'code_sent_at' => now()->timestamp,
            'expires_at' => $this->verificationCodeService->getCodeExpiryTimestamp(),
            'resend_count' => ($existingData['resend_count'] ?? 0) + 1
        ];
        
        // Store verification data with fingerprint-based key
        $cacheKey = "device_verification_by_fingerprint:{$fingerprint}";
        Cache::put($cacheKey, $verificationData, now()->addMinutes($this->verificationCodeService->getExpiryMinutes()));
        
        // Send verification email
        $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
        
        $resendAvailableAt = $this->verificationCodeService->getResendAvailableTimestamp();
        
        return response()->json([
            'message' => 'New verification code sent to your email',
            'code_expires_at' => $verificationData['expires_at'],
            'resend_available_at' => $resendAvailableAt,
            'expires_in' => config('authentication.email.verification_expire_minutes', 15) . ' minutes',
            'resend_cooldown' => 60
        ]);
    }

    /**
     * Get device activity history
     */
    public function getActivity(Request $request, $deviceId): JsonResponse
    {
        $this->authorize('view', DeviceToken::class);
        $device = $request->user()->devices()->findOrFail($deviceId);
        
        // Get security events for this device
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $events = $securityService->getSecurityEvents($request->user()->id);
        
        return response()->json([
            'device' => $device,
            'activity' => $events,
            'last_login' => $device->last_used_at,
            'total_logins' => 0, // Can be calculated from activity logs
            'security_score' => $this->calculateSecurityScore($device)
        ]);
    }

    /**
     * Check for suspicious device activity
     */
    public function checkSuspiciousActivity(Request $request): JsonResponse
    {
        $this->authorize('manage', DeviceToken::class);
        $user = $request->user();
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        
        $suspiciousActivity = $securityService->checkSuspiciousActivity($user->id);
        
        return response()->json([
            'has_suspicious_activity' => $suspiciousActivity['detected'],
            'risk_level' => $suspiciousActivity['risk_level'],
            'recommendations' => $suspiciousActivity['recommendations'],
            'alerts' => $suspiciousActivity['alerts'] ?? []
        ]);
    }

    private function calculateSecurityScore($device): int
    {
        $score = 100;
        
        // Reduce score for untrusted devices
        if (!$device->is_trusted) $score -= 20;
        
        // Reduce score for old devices
        if ($device->last_used_at < now()->subDays($this->timeoutService->getDeviceTokenInactivityLimit())) $score -= 10;
        
        // Reduce score for suspicious IPs
        if ($this->isSuspiciousIP($device->ip_address)) $score -= 30;
        
        return max(0, $score);
    }
    
    private function isSuspiciousIP(string $ip): bool
    {
        // Simple check - in production use proper IP reputation service
        return false;
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

    /**
     * Unregister device by token (legacy method)
     */
    public function unregister(Request $request, $token): JsonResponse
    {
        $this->authorize('revoke', DeviceToken::class);
        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $token)
            ->delete();

        return response()->json([
            'message' => 'Device unregistered successfully',
        ]);
    }
}
