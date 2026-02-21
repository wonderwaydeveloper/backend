<?php

namespace App\Services;

use Illuminate\Support\Facades\{DB, Cache};

class QueryOptimizationService
{
    public function getOptimizedTimelineQuery(int $userId): string
    {
        return "
            SELECT p.id, p.user_id, p.content, p.created_at,
                   u.name, u.username, u.avatar
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            INNER JOIN follows f ON p.user_id = f.following_id
            WHERE f.follower_id = {$userId} AND p.is_draft = 0
            ORDER BY p.created_at DESC
            LIMIT 20
        ";
    }

    public function getOptimizedSearchQuery(string $query): string
    {
        $sanitized = addslashes($query);
        return "
            SELECT p.id, p.content, p.created_at,
                   u.name, u.username
            FROM posts p
            INNER JOIN users u ON p.user_id = u.id
            WHERE MATCH(p.content) AGAINST('{$sanitized}' IN NATURAL LANGUAGE MODE)
            AND p.is_draft = 0
            ORDER BY MATCH(p.content) AGAINST('{$sanitized}' IN NATURAL LANGUAGE MODE) DESC
            LIMIT 50
        ";
    }

    public function optimizeTimelineQuery(int $userId, int $limit = 20): array
    {
        return Cache::remember("optimized_timeline:{$userId}:{$limit}", config('performance.cache.timeline'), function () use ($userId, $limit) {
            // Single optimized query
            return DB::select("
                SELECT p.id, p.user_id, p.content, p.image, p.created_at,
                       u.name, u.username, u.avatar,
                       COUNT(DISTINCT l.id) as likes_count,
                       COUNT(DISTINCT c.id) as comments_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                JOIN follows f ON p.user_id = f.following_id
                LEFT JOIN likes l ON p.id = l.likeable_id AND l.likeable_type = 'App\\\\Models\\\\Post'
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE f.follower_id = ? AND p.is_draft = 0
                GROUP BY p.id, p.user_id, p.content, p.image, p.created_at, u.name, u.username, u.avatar
                ORDER BY p.created_at DESC
                LIMIT ?
            ", [$userId, $limit]);
        });
    }

    public function batchLoadUserLikes(array $postIds, int $userId): array
    {
        if (empty($postIds)) return [];
        
        $placeholders = str_repeat('?,', count($postIds) - 1) . '?';
        
        return DB::select("
            SELECT likeable_id 
            FROM likes 
            WHERE user_id = ? AND likeable_id IN ({$placeholders}) AND likeable_type = 'App\\\\Models\\\\Post'
        ", array_merge([$userId], $postIds));
    }

    public function getPopularContent(int $hours = 24): array
    {
        return Cache::remember("popular_content:{$hours}h", config('performance.cache.popular_content'), function () use ($hours) {
            return DB::select("
                SELECT p.id, p.content, p.created_at,
                       u.name, u.username,
                       (COUNT(DISTINCT l.id) * 1 + COUNT(DISTINCT c.id) * 2) as engagement_score
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN likes l ON p.id = l.likeable_id AND l.likeable_type = 'App\\\\Models\\\\Post'
                LEFT JOIN comments c ON p.id = c.post_id
                WHERE p.created_at > DATE_SUB(NOW(), INTERVAL ? HOUR) AND p.is_draft = 0
                GROUP BY p.id, p.content, p.created_at, u.name, u.username
                HAVING engagement_score > 0
                ORDER BY engagement_score DESC
                LIMIT 50
            ", [$hours]);
        });
    }

    public function optimizeSearchQuery(string $query, int $limit = 20): array
    {
        $sanitized = addslashes($query);
        
        return Cache::remember("search:{$sanitized}:{$limit}", config('performance.cache.search'), function () use ($sanitized, $limit) {
            return DB::select("
                SELECT p.id, p.content, p.created_at,
                       u.name, u.username, u.avatar,
                       MATCH(p.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE MATCH(p.content) AGAINST(? IN NATURAL LANGUAGE MODE) AND p.is_draft = 0
                ORDER BY relevance DESC, p.created_at DESC
                LIMIT ?
            ", [$sanitized, $sanitized, $limit]);
        });
    }

    public function getQueryStats(): array
    {
        return [
            'slow_queries' => $this->getSlowQueries(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'avg_query_time' => $this->getAverageQueryTime()
        ];
    }

    private function getSlowQueries(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Slow_queries'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRatio(): float
    {
        // Simplified cache hit ratio calculation
        return 85.5; // Mock value
    }

    private function getAverageQueryTime(): float
    {
        // Simplified average query time
        return 12.3; // Mock value in ms
    }
}