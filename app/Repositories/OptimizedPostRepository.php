<?php

namespace App\Repositories;

use App\Models\Post;
use App\Services\CacheOptimizationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

class OptimizedPostRepository
{
    public function __construct(
        private CacheOptimizationService $cacheService
    ) {}

    public function create(array $data): Post
    {
        $post = Post::create($data);
        $this->cacheService->invalidateUserCache($post->user_id);
        return $post;
    }

    public function findById(int $id): ?Post
    {
        return Cache::tags(['post', "post:{$id}"])
            ->remember("post:{$id}", 1800, function () use ($id) {
                return Post::select([
                        'id', 'user_id', 'content', 'image', 'gif_url',
                        'quoted_post_id', 'created_at', 'updated_at'
                    ])
                    ->with([
                        'user:id,name,username,avatar',
                        'hashtags:id,name,slug'
                    ])
                    ->withCount(['likes', 'comments', 'quotes'])
                    ->find($id);
            });
    }

    public function findWithRelations(int $id, array $relations = []): ?Post
    {
        return Post::select($this->getOptimizedColumns())
            ->with($relations)
            ->find($id);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        $this->cacheService->invalidatePostCache($post->id);
        return $post->fresh();
    }

    public function delete(Post $post): bool
    {
        $this->cacheService->invalidatePostCache($post->id);
        return $post->delete();
    }

    public function getPublicPosts(int $page = 1, int $perPage = 20): LengthAwarePaginator
    {
        return Cache::tags(['posts', 'public'])
            ->remember("public_posts:{$page}:{$perPage}", 300, function () use ($page, $perPage) {
                return Post::select($this->getOptimizedColumns())
                    ->with($this->getOptimizedRelations())
                    ->withCount($this->getCountRelations())
                    ->published()
                    ->whereNull('thread_id')
                    ->latest('published_at')
                    ->paginate($perPage, ['*'], 'page', $page);
            });
    }

    public function getTimelinePosts(int $userId, int $limit = 20): Collection
    {
        $followingIds = $this->cacheService->getCachedFollowingIds($userId);
        
        return Cache::tags(['timeline', "user:{$userId}"])
            ->remember("timeline_posts:{$userId}:{$limit}", 300, function () use ($followingIds, $limit) {
                return Post::select($this->getOptimizedColumns())
                    ->with($this->getOptimizedRelations())
                    ->withCount($this->getCountRelations())
                    ->whereIn('user_id', $followingIds)
                    ->published()
                    ->whereNull('thread_id')
                    ->latest('published_at')
                    ->limit($limit)
                    ->get();
            });
    }

    public function getUserDrafts(int $userId): LengthAwarePaginator
    {
        return Post::select($this->getOptimizedColumns())
            ->where('user_id', $userId)
            ->where('is_draft', true)
            ->with(['hashtags:id,name,slug'])
            ->latest()
            ->paginate(20);
    }

    public function getPostQuotes(int $postId): LengthAwarePaginator
    {
        return Cache::tags(['quotes', "post:{$postId}"])
            ->remember("post_quotes:{$postId}", 600, function () use ($postId) {
                return Post::select($this->getOptimizedColumns())
                    ->where('quoted_post_id', $postId)
                    ->with($this->getOptimizedRelations())
                    ->withCount($this->getCountRelations())
                    ->published()
                    ->latest('created_at')
                    ->paginate(20);
            });
    }

    public function getUserPosts(int $userId, int $limit = 20): Collection
    {
        return Cache::tags(['user_posts', "user:{$userId}"])
            ->remember("user_posts:{$userId}:{$limit}", 600, function () use ($userId, $limit) {
                return Post::select($this->getOptimizedColumns())
                    ->where('user_id', $userId)
                    ->with($this->getOptimizedRelations())
                    ->withCount($this->getCountRelations())
                    ->published()
                    ->whereNull('thread_id')
                    ->latest('published_at')
                    ->limit($limit)
                    ->get();
            });
    }

    public function searchPosts(string $query, int $limit = 20): Collection
    {
        $sanitizedQuery = $this->sanitizeSearchQuery($query);
        $cacheKey = "search_posts:" . md5($sanitizedQuery) . ":{$limit}";
        
        return Cache::tags(['search', 'posts'])
            ->remember($cacheKey, 300, function () use ($sanitizedQuery, $limit) {
                return Post::select($this->getOptimizedColumns())
                    ->where('content', 'LIKE', "%{$sanitizedQuery}%")
                    ->with($this->getOptimizedRelations())
                    ->withCount($this->getCountRelations())
                    ->published()
                    ->whereNull('thread_id')
                    ->latest('published_at')
                    ->limit($limit)
                    ->get();
            });
    }

    public function getBatchPostsWithLikes(array $postIds, int $userId): Collection
    {
        // Single query to get posts
        $posts = Post::select($this->getOptimizedColumns())
            ->whereIn('id', $postIds)
            ->with($this->getOptimizedRelations())
            ->withCount($this->getCountRelations())
            ->get()
            ->keyBy('id');

        // Single query to get user likes
        $userLikes = DB::table('likes')
            ->where('user_id', $userId)
            ->whereIn('likeable_id', $postIds)
            ->where('likeable_type', Post::class)
            ->pluck('likeable_id')
            ->toArray();

        // Add like status to posts
        $posts->transform(function ($post) use ($userLikes) {
            $post->is_liked = in_array($post->id, $userLikes);
            return $post;
        });

        return $posts->values();
    }

    private function getOptimizedColumns(): array
    {
        return [
            'id', 'user_id', 'content', 'image', 'gif_url',
            'quoted_post_id', 'thread_id', 'is_draft',
            'published_at', 'created_at', 'updated_at'
        ];
    }

    private function getOptimizedRelations(): array
    {
        return [
            'user:id,name,username,avatar',
            'hashtags:id,name,slug',
            'quotedPost:id,content,user_id',
            'quotedPost.user:id,name,username'
        ];
    }

    private function getCountRelations(): array
    {
        return ['likes', 'comments', 'quotes'];
    }

    private function sanitizeSearchQuery(string $query): string
    {
        $query = preg_replace('/[%_\\\\]/', '\\\\$0', $query);
        $query = str_replace(chr(0), '', $query);
        return trim(substr($query, 0, 100));
    }
}