<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThreadRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Rules\{ContentLength, FileUpload};
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    /**
     * Create a new thread
     */
    public function create(ThreadRequest $request)
    {
        $this->authorize('create', Post::class);
        
        $validated = $request->validated();
        $user = $request->user();
        $firstPost = null;
        $position = 1;

        foreach ($validated['posts'] as $postData) {
            $data = [
                'user_id' => $user->id,
                'content' => $postData['content'],
                'is_draft' => false,
                'published_at' => now(),
            ];

            if ($firstPost) {
                $data['thread_id'] = $firstPost->id;
                $data['thread_position'] = $position++;
            }

            $post = Post::create($data);
            $post->syncHashtags();

            if (! $firstPost) {
                $firstPost = $post;
            }
        }

        broadcast(new \App\Events\PostPublished($firstPost->load('user:id,name,username,avatar')));

        return response()->json(
            new PostResource($firstPost->load('threadPosts.user', 'user', 'hashtags'))
        , 201);
    }

    /**
     * Show thread with all posts
     */
    public function show(Post $post)
    {
        $threadRoot = $post->getThreadRoot();

        $threadRoot->load([
            'threadPosts.user:id,name,username,avatar',
            'threadPosts.hashtags',
            'threadPosts.quotedPost.user:id,name,username,avatar',
            'user:id,name,username,avatar',
            'hashtags',
            'quotedPost.user:id,name,username,avatar',
        ])->loadCount('likes', 'comments', 'quotes');

        $threadRoot->threadPosts->each(function ($threadPost) {
            $threadPost->loadCount('likes', 'comments', 'quotes');
        });

        return response()->json([
            'thread_root' => new PostResource($threadRoot),
            'thread_posts' => PostResource::collection($threadRoot->threadPosts),
            'total_posts' => $threadRoot->threadPosts->count() + 1,
        ]);
    }

    /**
     * Add post to existing thread
     */
    public function addToThread(Request $request, Post $post)
    {
        $this->authorize('create', Post::class);
        
        $request->validate([
            'content' => ['required', new ContentLength('post')],
            'image' => ['nullable', new FileUpload('avatar')],
        ]);

        $threadRoot = $post->getThreadRoot();
        $lastPosition = $threadRoot->threadPosts()->max('thread_position') ?? 0;

        $data = [
            'user_id' => $request->user()->id,
            'content' => $request->input('content'),
            'thread_id' => $threadRoot->id,
            'thread_position' => $lastPosition + 1,
            'is_draft' => false,
            'published_at' => now(),
        ];

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $newPost = Post::create($data);
        $newPost->syncHashtags();
        $mentionedUsers = $newPost->processMentions($data['content']);

        foreach ($mentionedUsers as $mentionedUser) {
            $mentionedUser->notify(new \App\Notifications\MentionNotification($request->user(), $newPost));
        }

        broadcast(new \App\Events\PostPublished($newPost->load('user:id,name,username,avatar')));

        $newPost->load('user:id,name,username,avatar', 'hashtags');

        return new PostResource($newPost);
    }

    /**
     * Get thread statistics
     */
    public function stats(Post $post)
    {
        $threadRoot = $post->getThreadRoot();
        $threadPostIds = $threadRoot->threadPosts()->pluck('id')->push($threadRoot->id);
        
        $stats = [
            'total_posts' => $threadPostIds->count(),
            'total_likes' => DB::table('likes')
                ->whereIn('likeable_id', $threadPostIds)
                ->where('likeable_type', Post::class)
                ->count(),
            'total_comments' => DB::table('comments')
                ->whereIn('post_id', $threadPostIds)
                ->count(),
            'participants' => DB::table('posts')
                ->whereIn('id', $threadPostIds)
                ->distinct('user_id')
                ->count('user_id'),
            'created_at' => $threadRoot->created_at,
            'last_updated' => $threadRoot->threadPosts()->latest()->value('created_at') ?? $threadRoot->created_at,
        ];

        return response()->json($stats);
    }
}
