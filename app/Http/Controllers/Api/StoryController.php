<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoryRequest;
use App\Http\Resources\StoryResource;
use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $followingIds = $user->following()->pluck('users.id')->push($user->id);

        $stories = Story::active()
            ->whereIn('user_id', $followingIds)
            ->with('user:id,name,username,avatar')
            ->latest()
            ->get();

        return response()->json([
            'data' => StoryResource::collection($stories)
        ]);
    }

    public function store(StoryRequest $request)
    {
        $validated = $request->validated();
        
        $mediaType = $request->file('media')->getMimeType();
        $mediaType = str_starts_with($mediaType, 'video') ? 'video' : 'image';

        $mediaUrl = $request->file('media')->store('stories', 'public');

        $story = Story::create([
            'user_id' => $request->user()->id,
            'media_type' => $mediaType,
            'media_url' => $mediaUrl,
            'content' => $validated['content'] ?? null,
            'duration' => $validated['duration'] ?? 15,
            'background_color' => $validated['background_color'] ?? null,
            'font_style' => $validated['font_style'] ?? 'normal',
            'is_close_friends' => $validated['is_close_friends'] ?? false,
            'expires_at' => now()->addHours(24),
        ]);

        return response()->json(
            new StoryResource($story)
        , 201);
    }

    public function destroy(Story $story)
    {
        if ($story->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($story->media_url);
        $story->delete();

        return response()->json(['message' => 'استوری حذف شد']);
    }

    public function view(Story $story)
    {
        $story->increment('views_count');

        return response()->json(['message' => 'مشاهده شد']);
    }
}
