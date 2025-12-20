<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PostRepository
{
    public function getPublishedPosts(int $perPage = null): LengthAwarePaginator
    {
        return Post::published()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->latest()
            ->paginate($perPage ?? config('pagination.posts'));
    }

    public function getTimelinePosts(array $userIds, int $perPage = null): LengthAwarePaginator
    {
        return Post::published()
            ->with('user:id,name,username,avatar', 'hashtags')
            ->withCount('likes', 'comments')
            ->whereIn('user_id', $userIds)
            ->latest()
            ->paginate($perPage ?? config('pagination.posts'));
    }

    public function getUserDrafts(int $userId, int $perPage = null): LengthAwarePaginator
    {
        return Post::where('user_id', $userId)
            ->drafts()
            ->latest()
            ->paginate($perPage ?? config('pagination.posts'));
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }
}
