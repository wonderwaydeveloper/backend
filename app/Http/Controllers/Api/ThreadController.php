<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'posts' => 'required|array|min:2|max:25',
            'posts.*.content' => 'required|string|max:280',
            'posts.*.image' => 'nullable|image|max:2048',
        ]);

        $user = $request->user();
        $firstPost = null;
        $position = 1;

        foreach ($request->posts as $postData) {
            $data = [
                'user_id' => $user->id,
                'content' => $postData['content'],
                'is_draft' => false,
                'published_at' => now(),
            ];

            if (isset($postData['image'])) {
                $data['image'] = $postData['image']->store('posts', 'public');
            }

            if ($firstPost) {
                $data['thread_id'] = $firstPost->id;
                $data['thread_position'] = $position++;
            }

            $post = Post::create($data);
            $post->syncHashtags();

            if (!$firstPost) {
                $firstPost = $post;
            }
        }

        $firstPost->load(['threadPosts.user:id,name,username,avatar', 'user:id,name,username,avatar']);

        return response()->json($firstPost, 201);
    }

    public function show(Post $post)
    {
        if ($post->thread_id) {
            $post = Post::find($post->thread_id);
        }

        $post->load([
            'threadPosts.user:id,name,username,avatar',
            'threadPosts.hashtags',
            'user:id,name,username,avatar',
            'hashtags'
        ])
        ->loadCount('likes', 'comments');

        $post->threadPosts->each(function ($threadPost) {
            $threadPost->loadCount('likes', 'comments');
        });

        return response()->json($post);
    }
}
