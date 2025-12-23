<?php

namespace App\Services;

use Illuminate\Support\Facades\{DB, Cache};

class QueryOptimizationService
{
    public function optimizePostQueries(): void
    {
        // Add indexes for better performance
        DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_user_created ON posts(user_id, created_at DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published_at) WHERE published_at IS NOT NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_likes_post_user ON likes(likeable_id, user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_follows_follower ON follows(follower_id, following_id)');
    }

    public function getOptimizedTimeline(int $userId, int $limit = 20): array
    {
        return Cache::remember("timeline:v2:{$userId}:{$limit}", 300, function () use ($userId, $limit) {
            return DB::select("
                WITH user_posts AS (
                    SELECT p.id, p.content, p.created_at, p.likes_count, p.comments_count,
                           u.name, u.username, u.avatar, p.user_id,
                           EXISTS(SELECT 1 FROM likes l WHERE l.likeable_id = p.id AND l.user_id = ?) as is_liked
                    FROM posts p
                    INNER JOIN users u ON p.user_id = u.id
                    WHERE (p.user_id IN (
                        SELECT following_id FROM follows WHERE follower_id = ?
                    ) OR p.user_id = ?)
                    AND p.published_at IS NOT NULL
                    ORDER BY p.created_at DESC
                    LIMIT ?
                )
                SELECT * FROM user_posts
            ", [$userId, $userId, $userId, $limit]);
        });
    }

    public function warmupCache(): void
    {
        // Warm up popular queries
        $this->warmupTrendingPosts();
        $this->warmupPopularHashtags();
    }

    private function warmupTrendingPosts(): void
    {
        Cache::remember('posts:trending:24h', 1800, function () {
            return DB::select("
                SELECT p.*, u.name, u.username, u.avatar,
                       (p.likes_count * 2 + p.comments_count * 3 + p.quotes_count) as score
                FROM posts p
                INNER JOIN users u ON p.user_id = u.id
                WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND p.published_at IS NOT NULL
                ORDER BY score DESC
                LIMIT 100
            ");
        });
    }

    private function warmupPopularHashtags(): void
    {
        Cache::remember('hashtags:trending', 3600, function () {
            return DB::select("
                SELECT h.name, COUNT(ph.post_id) as posts_count
                FROM hashtags h
                INNER JOIN post_hashtags ph ON h.id = ph.hashtag_id
                INNER JOIN posts p ON ph.post_id = p.id
                WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY h.id, h.name
                ORDER BY posts_count DESC
                LIMIT 20
            ");
        });
    }
}