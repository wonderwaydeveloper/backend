<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UnifiedDeviceController extends Controller
{
    public function __construct(private PushNotificationService $pushService) {}

    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'browser' => 'nullable|string',
            'os' => 'nullable|string'
        ]);

        $fingerprint = $this->generateFingerprint($request);
        
        $device = DeviceToken::updateOrCreate([
            'user_id' => $request->user()->id,
            'token' => $request->token,
        ], [
            'device_type' => $request->platform,
            'device_name' => $request->device_name,
            'browser' => $request->browser,
            'os' => $request->os,
            'fingerprint' => $fingerprint,
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

    public function list(Request $request)
    {
        return $request->user()->devices()
            ->select(['id', 'device_name', 'device_type', 'browser', 'os', 'ip_address', 'last_used_at', 'is_trusted'])
            ->orderBy('last_used_at', 'desc')
            ->get();
    }

    public function trust(Request $request, $deviceId)
    {
        $request->validate(['password' => 'required']);

        if (!Hash::check($request->password, $request->user()->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }

        $request->user()->devices()->findOrFail($deviceId)->update(['is_trusted' => true]);
        return response()->json(['message' => 'Device trusted']);
    }

    public function revoke(Request $request, $deviceId)
    {
        $request->user()->devices()->findOrFail($deviceId)->delete();
        return response()->json(['message' => 'Device revoked']);
    }

    public function sendTestNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'nullable|array'
        ]);

        $devices = $request->user()->devices()->where('active', true)->get();
        $sent = 0;

        foreach ($devices as $device) {
            if ($this->pushService->sendToDevice($device->token, $request->title, $request->body, $request->data ?? [])) {
                $sent++;
            }
        }

        return response()->json(['sent_to' => $sent, 'total_devices' => $devices->count()]);
    }

    private function generateFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent(),
            $request->header('accept-language', ''),
            $request->ip(),
            $request->input('screen_resolution', ''),
            $request->input('timezone', '')
        ]));
    }
}