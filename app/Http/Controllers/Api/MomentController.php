<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MomentRequest;
use App\Http\Resources\MomentResource;
use App\Models\Moment;
use App\Models\Post;
use Illuminate\Http\Request;

class MomentController extends Controller
{
    public function index(Request $request)
    {
        $moments = Moment::public()
            ->with(['user:id,name,username,avatar'])
            ->withCount('posts')
            ->when($request->featured, fn ($q) => $q->featured())
            ->latest()
            ->paginate(20);

        return MomentResource::collection($moments);
    }

    public function store(MomentRequest $request)
    {
        $validated = $request->validated();

        $moment = Moment::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'privacy' => $validated['privacy'] ?? 'public',
            'cover_image' => $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('moments', 'public')
                : null,
        ]);

        // Add posts to moment if provided
        if (isset($validated['post_ids']) && is_array($validated['post_ids'])) {
            foreach ($validated['post_ids'] as $index => $postId) {
                $moment->addPost($postId, $index);
            }
        }

        $moment->load('creator', 'posts');

        return new MomentResource($moment);
    }

    public function show(Moment $moment)
    {
        if ($moment->privacy === 'private' && $moment->user_id !== auth()->id()) {
            return response()->json(['message' => 'Moment not found'], 404);
        }

        $moment->load([
            'user:id,name,username,avatar',
            'posts.user:id,name,username,avatar',
            'posts.hashtags:id,name,slug',
        ])->loadCount('posts');

        $moment->incrementViews();

        return new MomentResource($moment);
    }

    public function update(MomentRequest $request, Moment $moment)
    {
        $this->authorize('update', $moment);

        $validated = $request->validated();
        $moment->update($validated);

        return new MomentResource($moment);
    }

    public function destroy(Moment $moment)
    {
        $this->authorize('delete', $moment);

        $moment->delete();

        return response()->json(['message' => 'Moment deleted successfully']);
    }

    public function addPost(Request $request, Moment $moment)
    {
        $this->authorize('update', $moment);

        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'position' => 'nullable|integer|min:0',
        ]);

        if ($moment->posts()->where('post_id', $request->post_id)->exists()) {
            return response()->json(['message' => 'Post already in moment'], 409);
        }

        $moment->addPost($request->post_id, $request->position);

        return response()->json(['message' => 'Post added to moment']);
    }

    public function removePost(Request $request, Moment $moment, Post $post)
    {
        $this->authorize('update', $moment);

        if (! $moment->posts()->where('post_id', $post->id)->exists()) {
            return response()->json(['message' => 'Post not in moment'], 404);
        }

        $moment->removePost($post->id);

        return response()->json(['message' => 'Post removed from moment']);
    }

    public function myMoments(Request $request)
    {
        $moments = $request->user()
            ->moments()
            ->withCount('posts')
            ->latest()
            ->paginate(20);

        return MomentResource::collection($moments);
    }

    public function featured()
    {
        $moments = Moment::public()
            ->featured()
            ->with(['user:id,name,username,avatar'])
            ->withCount('posts')
            ->latest()
            ->limit(10)
            ->get();

        return MomentResource::collection($moments);
    }
}
