<?php

namespace App\Repositories;

use App\Contracts\PostRepositoryInterface;
use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PostRepository implements PostRepositoryInterface
{
    public function find(int $id): ?Post
    {
        return Post::find($id);
    }
    
    public function create(array $data): Post
    {
        return Post::create($data);
    }
    
    public function update(Post $post, array $data): bool
    {
        return $post->update($data);
    }
    
    public function delete(Post $post): bool
    {
        return $post->delete();
    }
    
    public function getPublicPosts(int $limit = 20): LengthAwarePaginator
    {
        return Post::published()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->latest('published_at')
            ->paginate($limit);
    }
    
    public function getUserPosts(int $userId, int $limit = 20): LengthAwarePaginator
    {
        return Post::where('user_id', $userId)
            ->published()
            ->with('user:id,name,username,avatar', 'hashtags')
            ->withCount('likes', 'comments')
            ->latest('published_at')
            ->paginate($limit);
    }
    
    public function getTimeline(int $userId, int $limit = 20): array
    {
        $followingIds = DB::table('follows')
            ->where('follower_id', $userId)
            ->pluck('following_id')
            ->toArray();
            
        $followingIds[] = $userId; // Include own posts
        
        return Post::published()
            ->with('user:id,name,username,avatar', 'hashtags')
            ->withCount('likes', 'comments')
            ->whereIn('user_id', $followingIds)
            ->latest('published_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    public function searchPosts(string $query, array $filters = []): array
    {
        $posts = Post::published()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->where('content', 'LIKE', "%{$query}%");
            
        if (isset($filters['user_id'])) {
            $posts->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['hashtag'])) {
            $posts->whereHas('hashtags', function ($q) use ($filters) {
                $q->where('name', $filters['hashtag']);
            });
        }
        
        return $posts->latest('published_at')
            ->limit(50)
            ->get()
            ->toArray();
    }
    
    public function getTrendingPosts(int $limit = 20): array
    {
        return Post::published()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('likes_count')
            ->orderByDesc('comments_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

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
}
