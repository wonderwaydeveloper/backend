<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use Illuminate\Http\Request;

class ScheduledPostController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:280',
            'scheduled_at' => 'required|date|after:now',
            'media_urls' => 'nullable|array',
            'reply_settings' => 'nullable|in:everyone,following,mentioned,none',
        ]);

        $scheduledPost = ScheduledPost::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'scheduled_at' => $request->scheduled_at,
            'media_urls' => $request->media_urls,
            'reply_settings' => $request->reply_settings ?? 'everyone',
        ]);

        return response()->json($scheduledPost, 201);
    }

    public function index(Request $request)
    {
        $scheduledPosts = ScheduledPost::where('user_id', $request->user()->id)
            ->where('published', false)
            ->orderBy('scheduled_at')
            ->paginate();

        return response()->json($scheduledPosts);
    }

    public function destroy(Request $request, $id)
    {
        $scheduledPost = ScheduledPost::findOrFail($id);

        if ($scheduledPost->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $scheduledPost->delete();

        return response()->json(['message' => 'Scheduled post deleted']);
    }
}
