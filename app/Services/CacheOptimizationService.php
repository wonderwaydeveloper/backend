<?php

namespace App\Services;

use App\Models\{Follow, Post, User};
use Illuminate\Support\Facades\{Cache, DB, Redis};

class CacheOptimizationService
{
    // ✅ تبدیل به method
    private function getCacheTags(): array
    {
        return [
            'timeline' => config('performance.cache.timeline'),
            'user' => config('performance.cache.user'),
            'post' => config('performance.cache.post'),
            'trending' => config('performance.cache.trending')
        ];
    }

    public function getCachedFollowingIds(int $userId): array
    {
        $tags = $this->getCacheTags();
        return Cache::remember(
            "following:{$userId}",
            $tags['user'],
            fn() => Follow::where('follower_id', $userId)
                ->pluck('following_id')
                ->push($userId)
                ->toArray()
        );
    }

    public function getOptimizedUserProfile(int $userId): array
    {
        $tags = $this->getCacheTags();
        return Cache::tags(['user', "profile:{$userId}"])
            ->remember("profile:{$userId}", $tags['user'], function () use ($userId) {
                return User::select(['id', 'name', 'username', 'avatar', 'bio'])
                    ->withCount(['followers', 'following', 'posts'])
                    ->findOrFail($userId)
                    ->toArray();
            });
    }

    public function getCachedTrendingPosts(int $limit = 20): array
    {
        $tags = $this->getCacheTags();
        return Cache::tags(['trending', 'posts'])
            ->remember('trending:posts', $tags['trending'], function () use ($limit) {
                return Post::select(['id', 'user_id', 'content', 'created_at'])
                    ->with(['user:id,name,username,avatar'])
                    ->withCount(['likes', 'comments'])
                    ->where('created_at', '>', now()->subHours(24))
                    ->orderByRaw('(likes_count + comments_count * 2) DESC')
                    ->limit($limit)
                    ->get()
                    ->toArray();
            });
    }

    public function warmupUserCache(int $userId): void
    {
        $tags = $this->getCacheTags();
        // Warm following cache
        $this->getCachedFollowingIds($userId);
        
        // Warm profile cache
        $this->getOptimizedUserProfile($userId);
        
        // Warm timeline cache
        $timelineData = $this->generateTimelineData($userId);
        Cache::tags(['timeline', "user:{$userId}"])
            ->put("timeline:{$userId}:1", $timelineData, $tags['timeline']);
    }

    public function invalidateUserCache(int $userId): void
    {
        Cache::tags(["user:{$userId}"])->flush();
        Cache::forget("following:{$userId}");
        Cache::forget("profile:{$userId}");
    }

    public function invalidatePostCache(int $postId): void
    {
        Cache::tags(['posts', "post:{$postId}"])->flush();
        Cache::tags(['timeline'])->flush(); // Invalidate all timelines
    }

    public function batchWarmup(array $userIds): array
    {
        $results = [];
        
        foreach ($userIds as $userId) {
            try {
                $this->warmupUserCache($userId);
                $results[$userId] = 'success';
            } catch (\Exception $e) {
                $results[$userId] = 'failed: ' . $e->getMessage();
            }
        }
        
        return $results;
    }

    public function getOptimizedTimeline(int $userId, int $page = 1): array
    {
        $tags = $this->getCacheTags();
        $cacheKey = "optimized_timeline:{$userId}:{$page}";
        
        return Cache::tags(['timeline', "user:{$userId}"])
            ->remember($cacheKey, $tags['timeline'], function () use ($userId, $page) {
                $followingIds = $this->getCachedFollowingIds($userId);
                $posts = $this->generateTimelineData($userId);
                
                return $posts; // Return posts directly, not wrapped in array
            });
    }

    public function cacheUserData(int $userId, array $data): void
    {
        $tags = $this->getCacheTags();
        Cache::tags(["user:{$userId}"])
            ->put("user_data:{$userId}", $data, $tags['user']);
    }

    public function getCachedUserData(int $userId): ?array
    {
        return Cache::tags(["user:{$userId}"])
            ->get("user_data:{$userId}");
    }

    private function generateTimelineData(int $userId): array
    {
        $followingIds = $this->getCachedFollowingIds($userId);
        
        return Post::select(['id', 'user_id', 'content', 'created_at'])
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->whereIn('user_id', $followingIds)
            ->published()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getCacheStats(): array
    {
        $redis = Redis::connection();
        
        return [
            'memory_usage' => $redis->info('memory')['used_memory_human'] ?? 'N/A',
            'total_keys' => $redis->dbsize(),
            'hit_ratio' => $this->calculateHitRatio(),
            'active_tags' => ['timeline', 'user', 'post', 'trending']
        ];
    }

    private function calculateHitRatio(): float
    {
        $redis = Redis::connection();
        $info = $redis->info('stats');
        
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        
        return $hits + $misses > 0 ? round(($hits / ($hits + $misses)) * 100, 2) : 0;
    }
}