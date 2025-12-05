<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Carbon;

class RedisService
{
    private $prefix;

    public function __construct()
    {
        $this->prefix = config('database.redis.options.prefix', 'laravel_database_');
    }

    /**
     * ذخیره کاربر آنلاین
     */
    public function setUserOnline(int $userId): void
    {
        $key = "user:online:{$userId}";
        Redis::setex($key, 300, 'online'); // 5 minutes
    }

    /**
     * بررسی آنلاین بودن کاربر
     */
    public function isUserOnline(int $userId): bool
    {
        $key = "user:online:{$userId}";
        return Redis::exists($key);
    }

    /**
     * دریافت تعداد کاربران آنلاین
     */
    public function getOnlineUsersCount(): int
    {
        $pattern = "user:online:*";
        $keys = Redis::keys($pattern);
        return count($keys);
    }

    /**
     * کش کردن پست‌های فید کاربر
     */
    public function cacheUserFeed(string $key, array $posts, int $ttl = 300): void
    {
        Redis::setex($key, $ttl, json_encode($posts));
    }

    /**
     * دریافت پست‌های فید از کش
     */
    public function getCachedUserFeed(string $key): ?array
    {
        try {
            $cached = Redis::get($key);
            return $cached ? json_decode($cached, true) : null;
        } catch (\Exception $e) {
            \Log::error("Redis error for key {$key}: " . $e->getMessage());
            return null;
        }
    }



    public function cachePosts($posts, $ttl = 3600): void
    {
        try {
            $key = "public_posts";
            $this->redis->setex($key, $ttl, json_encode($posts));
        } catch (\Exception $e) {
            \Log::error("Failed to cache posts: " . $e->getMessage());
        }
    }

    public function getCachedPosts(): ?array
    {
        try {
            $key = "public_posts";
            $cached = $this->redis->get($key);

            return $cached ? json_decode($cached, true) : null;
        } catch (\Exception $e) {
            \Log::error("Failed to get cached posts: " . $e->getMessage());
            return null;
        }
    }


    /**
     * کش کردن نتایج جستجو
     */
    public function cacheSearchResults(string $query, array $results, int $ttl = 600): void
    {
        $key = "search:" . md5($query);
        Redis::setex($key, $ttl, json_encode($results));
    }

    /**
     * دریافت نتایج جستجو از کش
     */
    public function getCachedSearchResults(string $query): ?array
    {
        $key = "search:" . md5($query);
        $data = Redis::get($key);

        return $data ? json_decode($data, true) : null;
    }

    /**
     * افزایش تعداد بازدید پست
     */
    public function incrementPostView(int $postId): int
    {
        $key = "post:views:{$postId}";
        return Redis::incr($key);
    }

    /**
     * دریافت تعداد بازدید پست
     */
    public function getPostViews(int $postId): int
    {
        $key = "post:views:{$postId}";
        return (int) Redis::get($key) ?: 0;
    }

    /**
     * مدیریت ریت لیمیت
     */
    public function checkRateLimit(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $key = "rate_limit:{$key}";

        $current = Redis::get($key);
        if ($current && $current >= $maxAttempts) {
            return false;
        }

        Redis::multi()
            ->incr($key)
            ->expire($key, $decaySeconds)
            ->exec();

        return true;
    }

    /**
     * کش کردن تنظیمات پلتفرم
     */
    public function cachePlatformSettings(array $settings): void
    {
        Redis::setex('platform:settings', 3600, json_encode($settings)); // 1 hour
    }

    /**
     * دریافت تنظیمات پلتفرم از کش
     */
    public function getCachedPlatformSettings(): ?array
    {
        $data = Redis::get('platform:settings');
        return $data ? json_decode($data, true) : null;
    }

    /**
     * مدیریت لاک برای عملیات اتمیک
     */
    public function acquireLock(string $lockName, int $ttl = 10): bool
    {
        $key = "lock:{$lockName}";
        return Redis::set($key, 1, 'NX', 'EX', $ttl);
    }

    /**
     * رها کردن لاک
     */
    public function releaseLock(string $lockName): void
    {
        $key = "lock:{$lockName}";
        Redis::del($key);
    }

    /**
     * پاک کردن کش بر اساس الگو
     */
    public function clearPattern(string $pattern): void
    {
        $keys = Redis::keys("{$this->prefix}{$pattern}");
        if (!empty($keys)) {
            // حذف prefix از کلیدها
            $keys = array_map(function ($key) {
                return str_replace($this->prefix, '', $key);
            }, $keys);

            Redis::del($keys);
        }
    }

    /**
     * دریافت آمار Redis
     */
    public function getStats(): array
    {
        $info = Redis::info();

        return [
            'used_memory' => $info['used_memory_human'] ?? '0',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'total_commands_processed' => $info['total_commands_processed'] ?? 0,
            'keyspace_hits' => $info['keyspace_hits'] ?? 0,
            'keyspace_misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $info['keyspace_hits'] && $info['keyspace_misses'] ?
                round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']) * 100, 2) : 0,
        ];
    }
}