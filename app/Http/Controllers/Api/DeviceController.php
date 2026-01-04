<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android,web',
        ]);

        // Generate a simple fingerprint for basic device registration
        $fingerprint = hash('sha256', $request->user()->id . $request->input('token') . $request->input('platform') . time());

        DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->input('token'),
            ],
            [
                'device_type' => $request->input('platform'),
                'fingerprint' => $fingerprint,
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully',
        ]);
    }

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
