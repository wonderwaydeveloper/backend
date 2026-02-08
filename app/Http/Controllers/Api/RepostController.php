<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RepostController extends Controller
{
    public function repost(Request $request, Post $post)
    {
        $request->validate([
            'quote' => 'nullable|string|max:280',
        ]);

        return DB::transaction(function () use ($request, $post) {
            $user = $request->user();
            $post = Post::lockForUpdate()->findOrFail($post->id);
            
            $existing = $user->reposts()->where('post_id', $post->id)->first();

            if ($existing) {
                $existing->delete();
                $post->decrement('reposts_count');

                return response()->json(['message' => 'Repost cancelled', 'reposted' => false]);
            }

            $repost = $user->reposts()->create([
                'post_id' => $post->id,
                'quote' => $request->quote,
            ]);

            $post->increment('reposts_count');

            $isQuote = ! empty($request->quote);
            event(new \App\Events\PostReposted($post, $user, $repost, $isQuote));

            return response()->json(['message' => 'Reposted successfully', 'reposted' => true, 'repost' => $repost], 201);
        });
    }

    public function unrepost(Request $request, Post $post)
    {
        return DB::transaction(function () use ($request, $post) {
            $user = $request->user();
            $post = Post::lockForUpdate()->findOrFail($post->id);
            
            $existing = $user->reposts()->where('post_id', $post->id)->first();

            if ($existing) {
                $existing->delete();
                $post->decrement('reposts_count');
                return response()->json(['message' => 'Repost cancelled', 'reposted' => false]);
            }

            return response()->json(['message' => 'Not reposted'], 400);
        });
    }

    public function reposts(Post $post)
    {
        $reposts = $post->reposts()->with('user:id,name,username,avatar')->paginate(20);
        return response()->json($reposts);
    }

    public function myReposts(Request $request)
    {
        $reposts = $request->user()
            ->reposts()
            ->with('post.user:id,name,username,avatar')
            ->latest()
            ->paginate(20);

        return response()->json($reposts);
    }
}
