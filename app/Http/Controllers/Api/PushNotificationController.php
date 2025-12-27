<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PushNotificationRequest;
use App\Http\Resources\DeviceResource;
use App\Models\DeviceToken;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    public function registerDevice(PushNotificationRequest $request)
    {
        $validated = $request->validated();

        try {
            $device = DeviceToken::updateOrCreate([
                'user_id' => auth()->id(),
                'token' => $validated['device_token'],
            ], [
                'device_type' => $validated['device_type'],
                'device_name' => $validated['app_version'] ?? null,
                'active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json(['message' => 'دستگاه با موفقیت ثبت شد', 'device_id' => $device->id]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'خطا در ثبت دستگاه'], 500);
        }
    }

    public function unregisterDevice(Request $request, $token)
    {
        try {
            DeviceToken::where('user_id', auth()->id())
                ->where('token', $token)
                ->update(['active' => false]);

            return response()->json(['message' => 'دستگاه غیرفعال شد']);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در غیرفعال کردن دستگاه',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function testNotification(PushNotificationRequest $request)
    {
        $validated = $request->validated();

        try {
            $devices = auth()->user()->devices()->where('active', true)->get();

            if ($devices->isEmpty()) {
                return response()->json(['message' => 'هیچ دستگاه فعالی یافت نشد'], 404);
            }

            $successCount = 0;
            foreach ($devices as $device) {
                $result = $this->pushService->sendToDevice(
                    $device->token,
                    $validated['title'],
                    $validated['body'],
                    $validated['data'] ?? []
                );
                if ($result) {
                    $successCount++;
                }
            }

            return response()->json([
                'message' => 'اعلان تست ارسال شد',
                'sent_to' => $successCount,
                'total_devices' => $devices->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'خطا در ارسال اعلان تست'], 500);
        }
    }

    public function getDevices(Request $request)
    {
        $devices = auth()->user()->devices()
            ->select('id', 'device_type', 'device_name', 'active', 'last_used_at', 'created_at')
            ->orderBy('last_used_at', 'desc')
            ->get();

        return DeviceResource::collection($devices);
    }
}
