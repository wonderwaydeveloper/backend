<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    /**
     * نمایش تمام پست‌ها
     */
    public function index(Request $request)
    {
        try {
            $posts = $this->postService->getPosts($request->user(), $request->all());

            return GenericResource::success(
                PostResource::collection($posts),
                'Posts retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد پست جدید
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'type' => 'sometimes|in:post,reply,quote',
            'parent_id' => 'sometimes|exists:posts,id',
            'original_post_id' => 'sometimes|exists:posts,id',
            'is_sensitive' => 'sometimes|boolean',
            'scheduled_at' => 'sometimes|date|after:now',
            'media' => 'sometimes|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4,mov|max:10240',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('create', Post::class);

            $post = $this->postService->createPost($request->user(), $request->all());

            return GenericResource::success(
                new PostResource($post->load('user', 'media')),
                'Post created successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * نمایش پست خاص
     */
    public function show(Request $request, Post $post)
    {
        try {
            $this->authorize('view', $post);

            $post->load('user', 'media', 'parent', 'originalPost', 'replies.user');
            $post->incrementViewCount();

            return GenericResource::success(
                new PostResource($post),
                'Post retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * آپدیت پست
     */
    public function update(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'is_sensitive' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('update', $post);

            $post = $this->postService->updatePost($post, $request->all());

            return GenericResource::success(
                new PostResource($post->load('user', 'media')),
                'Post updated successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پست
     */
    public function destroy(Request $request, Post $post)
    {
        try {
            $this->authorize('delete', $post);

            $this->postService->deletePost($post);

            return GenericResource::success(null, 'Post deleted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * لایک کردن پست
     */
    public function like(Request $request, Post $post)
    {
        try {
            $this->authorize('like', $post);

            $liked = $this->postService->toggleLike($request->user(), $post);

            return GenericResource::success([
                'liked' => $liked,
                'like_count' => $post->fresh()->like_count,
            ], $liked ? 'Post liked' : 'Post unliked');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * بازنشر پست
     */
    public function repost(Request $request, Post $post)
    {
        try {
            $this->authorize('repost', $post);

            $repost = $this->postService->repost($request->user(), $post);

            return GenericResource::success(
                new PostResource($repost->load('user', 'originalPost')),
                'Post reposted successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * افزودن به بوکمارک
     */
    public function bookmark(Request $request, Post $post)
    {
        try {
            $bookmarked = $this->postService->toggleBookmark($request->user(), $post);

            return GenericResource::success([
                'bookmarked' => $bookmarked,
            ], $bookmarked ? 'Post bookmarked' : 'Post removed from bookmarks');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت پست‌های کاربر
     */
    public function userPosts(Request $request, $userId)
    {
        try {
            $posts = $this->postService->getUserPosts($userId, $request->user(), $request->all());

            return GenericResource::success(
                PostResource::collection($posts),
                'User posts retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت فید کاربر
     */
    public function feed(Request $request)
    {
        try {
            $posts = $this->postService->getUserFeed($request->user(), $request->all());

            return GenericResource::success(
                PostResource::collection($posts),
                'Feed retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }
}