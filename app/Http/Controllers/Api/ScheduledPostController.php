<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ScheduledPost;
use Illuminate\Http\Request;

class ScheduledPostController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('create', ScheduledPost::class);
        
        $validated = $request->validate([
            'content' => 'required|string|max:280',
            'scheduled_at' => 'required|date|after:now',
            'media_urls' => 'nullable|array',
            'reply_settings' => 'nullable|in:everyone,following,mentioned,none',
        ]);

        $scheduledPost = ScheduledPost::create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
            'scheduled_at' => $validated['scheduled_at'],
            'media_urls' => $validated['media_urls'] ?? null,
            'reply_settings' => $validated['reply_settings'] ?? 'everyone',
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

    public function destroy(ScheduledPost $scheduledPost)
    {
        $this->authorize('delete', $scheduledPost);
        
        $scheduledPost->delete();

        return response()->json(['message' => 'Scheduled post deleted']);
    }
}
