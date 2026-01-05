<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationPreferenceRequest;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request)
    {
        $preferences = $request->user()->notification_preferences ?? [
            'email' => [
                'likes' => true,
                'comments' => true,
                'follows' => true,
                'mentions' => true,
                'reposts' => true,
                'messages' => true,
            ],
            'push' => [
                'likes' => true,
                'comments' => true,
                'follows' => true,
                'mentions' => true,
                'reposts' => true,
                'messages' => true,
            ],
            'in_app' => [
                'likes' => true,
                'comments' => true,
                'follows' => true,
                'mentions' => true,
                'reposts' => true,
                'messages' => true,
            ],
        ];

        return response()->json(['preferences' => $preferences]);
    }

    public function update(NotificationPreferenceRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();
        
        $user->notification_preferences = $validated['preferences'];
        $user->save();

        return response()->json(['message' => 'Notification preferences updated', 'preferences' => $user->notification_preferences]);
    }

    public function updateType(Request $request, $type)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        if (! in_array($type, ['email', 'push', 'in_app'])) {
            return response()->json(['message' => 'Invalid notification type'], 400);
        }

        $user = $request->user();
        $preferences = $user->notification_preferences ?? [];

        // Enable/disable all notifications for this type
        $preferences[$type] = array_fill_keys([
            'likes', 'comments', 'follows', 'mentions', 'reposts', 'messages',
        ], $request->enabled);

        $user->notification_preferences = $preferences;
        $user->save();

        return response()->json([
            'message' => "Notification {$type} " . ($request->enabled ? 'enabled' : 'disabled'),
            'preferences' => $user->notification_preferences,
        ]);
    }

    public function updateSpecific(Request $request, $type, $category)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $validTypes = ['email', 'push', 'in_app'];
        $validCategories = ['likes', 'comments', 'follows', 'mentions', 'reposts', 'messages'];

        if (! in_array($type, $validTypes) || ! in_array($category, $validCategories)) {
            return response()->json(['message' => 'Invalid notification type or category'], 400);
        }

        $user = $request->user();
        $preferences = $user->notification_preferences ?? [];

        if (! isset($preferences[$type])) {
            $preferences[$type] = [];
        }

        $preferences[$type][$category] = $request->enabled;
        $user->notification_preferences = $preferences;
        $user->save();

        return response()->json([
            'message' => "Settings for {$category} in {$type} updated",
            'preferences' => $user->notification_preferences,
        ]);
    }
}
