<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, DeviceToken};
use App\Services\{DeviceFingerprintService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function __construct(
        private EmailService $emailService
    ) {}
    /**
     * Register a new device (simple registration)
     */
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
        ]);

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
            'device_id' => $device->id,
            'requires_verification' => !$device->is_trusted,
            'message' => 'Device registered successfully',
        ]);
    }

    /**
     * Advanced device registration with detailed info
     */
    public function registerAdvanced(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:mobile,desktop,tablet',
            'browser' => 'nullable|string',
            'os' => 'nullable|string',
            'push_token' => 'nullable|string'
        ]);

        $fingerprint = DeviceFingerprintService::generate($request);
        
        $existingDevice = DeviceToken::where('user_id', $request->user()->id)
                                   ->where('fingerprint', $fingerprint)
                                   ->first();
        
        if ($existingDevice) {
            $existingDevice->update([
                'device_name' => $request->name,
                'device_type' => $request->type,
                'browser' => $request->browser,
                'os' => $request->os,
                'push_token' => $request->push_token,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_used_at' => now()
            ]);
            
            return response()->json([
                'device_id' => $existingDevice->id,
                'requires_verification' => !$existingDevice->is_trusted
            ]);
        }
        
        $device = DeviceToken::create([
            'user_id' => $request->user()->id,
            'token' => 'device_' . Str::random(40),
            'fingerprint' => $fingerprint,
            'device_name' => $request->name,
            'device_type' => $request->type,
            'browser' => $request->browser,
            'os' => $request->os,
            'push_token' => $request->push_token,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_used_at' => now(),
            'is_trusted' => false
        ]);

        return response()->json([
            'device_id' => $device->id,
            'requires_verification' => !$device->is_trusted
        ]);
    }

    /**
     * List user devices with current device detection
     */
    public function list(Request $request)
    {
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

        return response()->json($devices);
    }

    /**
     * Trust a device
     */
    public function trust(Request $request, $deviceId)
    {
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }

        $device = $request->user()->devices()->findOrFail($deviceId);
        $device->update(['is_trusted' => true]);

        return response()->json(['message' => 'Device trusted successfully']);
    }

    /**
     * Revoke a single device with security checks
     */
    public function revoke(Request $request, $deviceId)
    {
        $device = $request->user()->devices()->findOrFail($deviceId);
        $currentFingerprint = DeviceFingerprintService::generate($request);
        
        // Prevent revoking current device
        if ($device->fingerprint === $currentFingerprint) {
            return response()->json(['error' => 'Cannot revoke current device'], 422);
        }
        
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        
        // Revoke tokens for security
        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
        
        $device->delete();
        
        // Clear cached data
        Cache::forget("device_verification:{$user->id}:{$device->fingerprint}");

        return response()->json([
            'message' => 'Device revoked successfully',
            'warning' => 'Other sessions terminated for security'
        ]);
    }

    /**
     * Revoke all devices except current
     */
    public function revokeAll(Request $request)
    {
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }

        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $currentFingerprint = DeviceFingerprintService::generate($request);
        
        // Revoke all other tokens
        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

        // Remove all other devices
        $user->devices()
            ->where('fingerprint', '!=', $currentFingerprint)
            ->delete();

        return response()->json(['message' => 'All other devices revoked successfully']);
    }

    /**
     * Verify device for authentication
     */
    public function verifyDevice(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'fingerprint' => 'required|string'
        ]);
        
        $ip = $request->ip();
        $fingerprint = $request->fingerprint;
        
        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("device_verify:{$ip}:{$fingerprint}", 3, 1);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after'],
                'errors' => ['code' => ['Too many attempts. Please wait before trying again.']]
            ], 429);
        }
            
            $verificationData = null;
            $cacheKey = null;
            
            // Try to find verification session by fingerprint
            $code = $request->code;
            
            // Try different possible user IDs (this is a fallback approach)
            $foundData = null;
            $foundKey = null;
            
            // First try with authenticated user if available
            $user = auth()->user();
            if ($user) {
                $key = "device_verification:{$user->id}:{$fingerprint}";
                $data = Cache::get($key);
                if ($data && isset($data['code']) && $data['code'] === $code) {
                    $foundData = $data;
                    $foundKey = $key;
                }
            }
            
            // If not found, try to search by code and fingerprint in recent user IDs
            // FIXED: Use specific query instead of User::all()
            if (!$foundData) {
                // Get recent users (last 100) to search their verification sessions
                $recentUsers = User::select('id')
                    ->where('updated_at', '>=', now()->subHours(2))
                    ->orderBy('updated_at', 'desc')
                    ->limit(100)
                    ->pluck('id');
                
                foreach ($recentUsers as $userId) {
                    $key = "device_verification:{$userId}:{$fingerprint}";
                    $data = Cache::get($key);
                    if ($data && isset($data['code']) && $data['code'] === $code) {
                        $foundData = $data;
                        $foundKey = $key;
                        break;
                    }
                }
            }
            
            $verificationData = $foundData;
            $cacheKey = $foundKey;
            
            if (!$verificationData) {
                \Log::warning('Device verification failed - session not found', [
                    'fingerprint' => $fingerprint,
                    'code' => $code,
                    'ip' => $ip
                ]);
                return response()->json([
                    'error' => 'Invalid verification code or session expired',
                    'errors' => ['code' => ['Invalid or expired verification code']]
                ], 422);
            }
            
            if (now()->timestamp > $verificationData['expires_at']) {
                Cache::forget($cacheKey);
                return response()->json([
                    'error' => 'Verification code expired',
                    'errors' => ['code' => ['Verification code has expired. Please request a new one.']]
                ], 422);
            }
            
            $user = User::find($verificationData['user_id']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 422);
            }
            
            // Create or update device as trusted with atomic operations
            $deviceLockKey = "device_creation:{$user->id}:{$fingerprint}";
            $deviceLock = Cache::lock($deviceLockKey, 5);
            
            if (!$deviceLock->get()) {
                return response()->json(['error' => 'Device creation in progress'], 503);
            }
            
            try {
                $device = $user->devices()->where('fingerprint', $fingerprint)->first();
                
                if (!$device) {
                    $device = $user->devices()->create([
                        'token' => 'device_' . Str::random(40),
                        'fingerprint' => $fingerprint,
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
            } finally {
                $deviceLock->release();
            }
            
            Cache::forget($cacheKey);
            
            $token = $user->createToken('auth_token')->plainTextToken;
            
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
    public function resendDeviceCode(Request $request)
    {
        $request->validate([
            'fingerprint' => 'required|string',
            'user_id' => 'nullable|integer'
        ]);
        
        $fingerprint = $request->fingerprint;
        $userId = $request->user_id;
        
        // Use centralized rate limiting
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        $rateLimitResult = $securityService->checkRateLimit("device_resend:{$request->ip()}:{$fingerprint}", 5, 1);
        
        if (!$rateLimitResult['allowed']) {
            return response()->json([
                'error' => $rateLimitResult['error'],
                'retry_after' => $rateLimitResult['retry_after']
            ], 429);
        }
        // If no user_id provided, try to find verification session by fingerprint
        if (!$userId) {
            $recentUsers = User::orderBy('updated_at', 'desc')->limit(50)->pluck('id');
            
            foreach ($recentUsers as $id) {
                $key = "device_verification:{$id}:{$fingerprint}";
                $data = Cache::get($key);
                if ($data) {
                    $userId = $id;
                    break;
                }
            }
            
            if (!$userId) {
                return response()->json([
                    'error' => 'No verification session found. Please try logging in again.',
                    'errors' => ['session' => ['Verification session expired or not found']]
                ], 422);
            }
        }
        
        // Find user
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'error' => 'User not found',
                'errors' => ['user_id' => ['Invalid user ID']]
            ], 422);
        }
        
        // Check if verification session exists
        $existingKey = "device_verification:{$userId}:{$fingerprint}";
        $existingData = Cache::get($existingKey);
        if (!$existingData) {
            return response()->json([
                'error' => 'No verification session found. Please try logging in again.',
                'errors' => ['session' => ['Verification session expired or not found']]
            ], 422);
        }
        
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
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
            'expires_at' => now()->addMinutes(15)->timestamp,
            'resend_count' => ($existingData['resend_count'] ?? 0) + 1
        ];
        
        $cacheKey = "device_verification:{$userId}:{$fingerprint}";
        Cache::put($cacheKey, $verificationData, now()->addMinutes(15));
        
        // Send verification email
        $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
        
        $resendAvailableAt = now()->addSeconds(30)->timestamp;
        
        return response()->json([
            'message' => 'New verification code sent to your email',
            'code_expires_at' => $verificationData['expires_at'],
            'resend_available_at' => $resendAvailableAt,
            'expires_in' => '15 minutes',
            'resend_cooldown' => 30
        ]);
    }

    /**
     * Check for suspicious device activity
     */
    public function checkSuspiciousActivity(Request $request)
    {
        $user = $request->user();
        $securityService = app(\App\Services\SecurityMonitoringService::class);
        
        $suspiciousActivity = $securityService->checkSuspiciousActivity($user->id);
        
        return response()->json([
            'has_suspicious_activity' => $suspiciousActivity['detected'],
            'risk_level' => $suspiciousActivity['risk_level'],
            'recommendations' => $suspiciousActivity['recommendations']
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

    /**
     * Unregister device by token (legacy method)
     */
    public function unregister(Request $request, $token)
    {
        DeviceToken::where('user_id', $request->user()->id)
            ->where('token', $token)
            ->delete();

        return response()->json([
            'message' => 'Device unregistered successfully',
        ]);
    }
}
