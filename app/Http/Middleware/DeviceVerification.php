<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DeviceVerification
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return $next($request);
        }

        $fingerprint = $this->generateFingerprint($request);
        $device = $request->user()->devices()
            ->where('fingerprint', $fingerprint)
            ->first();

        // New device detected
        if (!$device) {
            return response()->json([
                'error' => 'NEW_DEVICE_DETECTED',
                'message' => 'Please verify this new device',
                'requires_verification' => true
            ], 403);
        }

        // Update last used
        $device->update([
            'last_used_at' => now(),
            'ip_address' => $request->ip()
        ]);

        // Check for suspicious activity
        if ($this->isSuspiciousActivity($request, $device)) {
            return response()->json([
                'error' => 'SUSPICIOUS_ACTIVITY',
                'message' => 'Additional verification required',
                'requires_verification' => true
            ], 403);
        }

        return $next($request);
    }

    private function generateFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent(),
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            $request->ip()
        ]));
    }

    private function isSuspiciousActivity(Request $request, $device): bool
    {
        // Check IP change for trusted devices
        if ($device->is_trusted && $device->ip_address !== $request->ip()) {
            return true;
        }

        // Check rapid requests
        $key = "device_requests:{$device->fingerprint}";
        $requests = Cache::get($key, 0);
        
        if ($requests > 100) { // More than 100 requests per minute
            return true;
        }

        Cache::increment($key, 1);
        Cache::expire($key, 60);

        return false;
    }
}