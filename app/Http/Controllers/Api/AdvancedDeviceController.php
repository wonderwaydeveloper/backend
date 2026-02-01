<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class AdvancedDeviceController extends Controller
{
    public function registerDevice(Request $request)
    {
        $request->validate([
            'fingerprint' => 'required|string',
            'name' => 'required|string|max:255',
            'type' => 'required|in:mobile,desktop,tablet',
            'browser' => 'nullable|string',
            'os' => 'nullable|string',
            'push_token' => 'nullable|string'
        ]);

        $fingerprint = $request->fingerprint;
        
        // Check if device already exists for this user
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
                'fingerprint' => $fingerprint,
                'requires_verification' => !$existingDevice->is_trusted
            ]);
        }
        
        $device = DeviceToken::create([
            'user_id' => $request->user()->id,
            'token' => 'device_' . \Illuminate\Support\Str::random(40),
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
            'fingerprint' => $fingerprint,
            'requires_verification' => !$device->is_trusted
        ]);
    }

    public function listDevices(Request $request)
    {
        $currentFingerprint = $this->generateFingerprint($request);
        
        // Clean up any duplicate devices for this user
        $this->cleanupDuplicateDevices($request->user());
        
        $devices = $request->user()->devices()
            ->select(['id', 'device_name', 'device_type', 'browser', 'os', 'ip_address', 'last_used_at', 'is_trusted', 'created_at', 'fingerprint'])
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($device) use ($currentFingerprint) {
                $device->is_current = $device->fingerprint === $currentFingerprint;
                unset($device->fingerprint); // Remove fingerprint from response
                return $device;
            });

        return response()->json($devices);
    }
    
    private function cleanupDuplicateDevices($user)
    {
        // Get current fingerprint
        $currentFingerprint = $this->generateFingerprint(request());
        
        // Get all devices for this user
        $devices = $user->devices()->get();
        
        // Group by similar characteristics (same IP, similar user agent)
        $deviceGroups = $devices->groupBy(function ($device) {
            return $device->ip_address . '|' . $device->browser . '|' . $device->os;
        });
        
        foreach ($deviceGroups as $group) {
            if ($group->count() > 1) {
                // Keep the current device if it exists in this group
                $currentDevice = $group->where('fingerprint', $currentFingerprint)->first();
                
                if ($currentDevice) {
                    // Delete all others in this group except current
                    $group->except($currentDevice->id)->each(function ($device) {
                        $device->delete();
                    });
                } else {
                    // Keep the most recent device and delete others
                    $latest = $group->sortByDesc('last_used_at')->first();
                    $group->except($latest->id)->each(function ($device) {
                        $device->delete();
                    });
                }
            }
        }
    }

    public function trustDevice(Request $request, $deviceId)
    {
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }

        $device = $request->user()->devices()->findOrFail($deviceId);
        $device->update(['is_trusted' => true]);

        return response()->json(['message' => 'Device trusted']);
    }

    public function revokeDevice(Request $request, $deviceId)
    {
        $device = $request->user()->devices()->findOrFail($deviceId);
        $currentFingerprint = $this->generateFingerprint($request);
        
        // Prevent revoking current device
        if ($device->fingerprint === $currentFingerprint) {
            return response()->json(['error' => 'Cannot revoke current device'], 422);
        }
        
        $user = $request->user();
        
        // For browser-based revocation, we need to revoke all tokens except current
        // since we can't identify which specific token belongs to which device
        $currentToken = $user->currentAccessToken();
        
        // Revoke all other tokens (this will log out other devices/tabs)
        $user->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
        
        // Delete the device record
        $device->delete();
        
        // Clear any cached verification data
        Cache::forget("device_verification:{$user->id}:{$device->fingerprint}");

        return response()->json([
            'message' => 'Device revoked successfully. Other sessions have been logged out.',
            'warning' => 'All other active sessions have been terminated for security.'
        ]);
    }

    public function revokeAllDevices(Request $request)
    {
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }

        // Keep current session
        $currentToken = $request->user()->currentAccessToken();
        
        // Revoke all other tokens
        $request->user()->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

        // Remove all devices except current
        $currentFingerprint = $this->generateFingerprint($request);
        $request->user()->devices()
            ->where('fingerprint', '!=', $currentFingerprint)
            ->delete();

        return response()->json(['message' => 'All other devices revoked']);
    }

    public function deviceActivity(Request $request, $deviceId)
    {
        $device = $request->user()->devices()->findOrFail($deviceId);
        
        $activity = Cache::get("device_activity:{$device->fingerprint}", []);
        
        return response()->json([
            'device' => $device,
            'recent_activity' => array_slice($activity, -10)
        ]);
    }

    private function generateFingerprint(Request $request): string
    {
        // Use a more stable fingerprint that doesn't change between requests
        return hash('sha256', implode('|', [
            $request->userAgent() ?? '',
            $request->ip() ?? ''
        ]));
    }

    public function checkSuspiciousActivity(Request $request)
    {
        $user = $request->user();
        $currentFingerprint = $this->generateFingerprint($request);
        
        // Check for new device
        $isNewDevice = !$user->devices()
            ->where('fingerprint', $currentFingerprint)
            ->exists();

        // Check for unusual location (simplified)
        $lastKnownIp = $user->devices()
            ->where('is_trusted', true)
            ->latest('last_used_at')
            ->value('ip_address');

        $suspiciousLocation = $lastKnownIp && $lastKnownIp !== $request->ip();

        return response()->json([
            'is_new_device' => $isNewDevice,
            'suspicious_location' => $suspiciousLocation,
            'requires_additional_verification' => $isNewDevice || $suspiciousLocation
        ]);
    }
}