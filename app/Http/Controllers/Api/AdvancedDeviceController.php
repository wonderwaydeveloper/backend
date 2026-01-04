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
            'device_name' => 'required|string|max:255',
            'device_type' => 'required|in:mobile,desktop,tablet',
            'browser' => 'nullable|string',
            'os' => 'nullable|string',
            'push_token' => 'nullable|string'
        ]);

        $fingerprint = $this->generateFingerprint($request);
        
        // Check if device already exists for this user
        $existingDevice = DeviceToken::where('user_id', $request->user()->id)
                                   ->where('fingerprint', $fingerprint)
                                   ->first();
        
        if ($existingDevice) {
            $existingDevice->update([
                'device_name' => $request->device_name,
                'device_type' => $request->device_type,
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
            'fingerprint' => $fingerprint,
            'device_name' => $request->device_name,
            'device_type' => $request->device_type,
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
        $devices = $request->user()->devices()
            ->select(['id', 'device_name', 'device_type', 'browser', 'os', 'ip_address', 'last_used_at', 'is_trusted', 'created_at'])
            ->orderBy('last_used_at', 'desc')
            ->get();

        return response()->json($devices);
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
        
        // Revoke all tokens for this device
        $request->user()->tokens()
            ->where('name', 'like', "%device:{$device->fingerprint}%")
            ->delete();
            
        $device->delete();

        return response()->json(['message' => 'Device revoked']);
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
        return hash('sha256', implode('|', [
            $request->userAgent(),
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            $request->ip(),
            $request->input('screen_resolution', ''),
            $request->input('timezone', '')
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