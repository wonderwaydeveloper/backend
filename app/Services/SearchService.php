<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Article;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SearchService
{

    public function __construct(
        private RedisService $redisService
    ) {
    }


    /**
     * جستجوی جامع در پلتفرم با کش
     */

    public function globalSearch(string $query, ?User $user = null, array $filters = []): array
    {
        $cacheKey = "global_search:" . md5($query . json_encode($filters));

        // بررسی کش
        $cached = $this->redisService->getCachedSearchResults($cacheKey);
        if ($cached) {
            return $cached;
        }

        $results = [];

        // جستجو در کاربران - اصلاح شرط
        if (!isset($filters['exclude_users']) || !$filters['exclude_users']) {
            $results['users'] = $this->searchUsers($query, $user, $filters);
        }

        // جستجو در پست‌ها - اصلاح شرط
        if (!isset($filters['exclude_posts']) || !$filters['exclude_posts']) {
            $results['posts'] = $this->searchPosts($query, $user, $filters);
        }

        // جستجو در مقالات - اصلاح شرط
        if (!isset($filters['exclude_articles']) || !$filters['exclude_articles']) {
            $results['articles'] = $this->searchArticles($query, $user, $filters);
        }

        // کش کردن نتایج
        $this->redisService->cacheSearchResults($cacheKey, $results);

        return $results;
    }

    /**
     * جستجو در کاربران
     */
    public function searchUsers(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator
    {
        $searchQuery = User::withCount(['followers', 'following', 'posts'])
            ->active()
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%")
                    ->orWhere('bio', 'like', "%{$query}%");
            });

        // فیلتر کاربران خصوصی برای کاربران لاگین نکرده
        if (!$user) {
            $searchQuery->where('is_private', false);
        }

        // اولویت‌بندی نتایج
        $searchQuery->orderByRaw("
            CASE 
                WHEN username = ? THEN 1
                WHEN username LIKE ? THEN 2
                WHEN name LIKE ? THEN 3
                WHEN bio LIKE ? THEN 4
                ELSE 5
            END
        ", [$query, "{$query}%", "{$query}%", "%{$query}%"]);

        return $searchQuery->paginate($filters['per_page'] ?? 10);
    }

    /**
     * جستجو در پست‌ها
     */
    public function searchPosts(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator
    {
        $searchQuery = Post::with(['user', 'media'])
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('content', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('username', 'like', "%{$query}%");
                    });
            });

        // فیلتر محتوای حساس برای کاربران زیر سن
        if ($user && $user->is_underage) {
            $searchQuery->where('is_sensitive', false);
        }

        // فیلتر بر اساس کاربر
        if (isset($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }

        $searchQuery->orderBy('created_at', 'desc');

        return $searchQuery->paginate($filters['per_page'] ?? 15);
    }

    /**
     * جستجو در مقالات
     */
    public function searchArticles(string $query, ?User $user = null, array $filters = []): LengthAwarePaginator
    {
        $searchQuery = Article::with(['user', 'media'])
            ->published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query) {
                        $userQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('username', 'like', "%{$query}%");
                    });
            });

        // فیلتر بر اساس تگ
        if (isset($filters['tag'])) {
            $searchQuery->whereJsonContains('tags', $filters['tag']);
        }

        // فیلتر مقالات تأیید شده
        if (isset($filters['approved']) && $filters['approved']) {
            $searchQuery->where('is_approved', true);
        }

        $searchQuery->orderBy('created_at', 'desc');

        return $searchQuery->paginate($filters['per_page'] ?? 10);
    }

    /**
     * جستجوی پیشرفته با فیلترهای بیشتر
     */
    public function advancedSearch(array $criteria, ?User $user = null): array
    {
        $results = [];

        if (isset($criteria['users'])) {
            $results['users'] = $this->advancedUserSearch($criteria['users'], $user);
        }

        if (isset($criteria['posts'])) {
            $results['posts'] = $this->advancedPostSearch($criteria['posts'], $user);
        }

        if (isset($criteria['articles'])) {
            $results['articles'] = $this->advancedArticleSearch($criteria['articles'], $user);
        }

        return $results;
    }

    /**
     * جستجوی پیشرفته کاربران
     */
    private function advancedUserSearch(array $criteria, ?User $user): LengthAwarePaginator
    {
        $query = User::withCount(['followers', 'following', 'posts'])
            ->active();

        if (isset($criteria['query'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('name', 'like', "%{$criteria['query']}%")
                    ->orWhere('username', 'like', "%{$criteria['query']}%")
                    ->orWhere('bio', 'like', "%{$criteria['query']}%");
            });
        }

        if (isset($criteria['verified']) && $criteria['verified']) {
            $query->where('is_verified', true);
        }

        if (isset($criteria['min_followers'])) {
            $query->having('followers_count', '>=', $criteria['min_followers']);
        }

        if (isset($criteria['location'])) {
            $query->where('location', 'like', "%{$criteria['location']}%");
        }

        return $query->paginate($criteria['per_page'] ?? 20);
    }

    /**
     * جستجوی پیشرفته پست‌ها
     */
    private function advancedPostSearch(array $criteria, ?User $user): LengthAwarePaginator
    {
        $query = Post::with(['user', 'media'])
            ->published();

        if (isset($criteria['query'])) {
            $query->where('content', 'like', "%{$criteria['query']}%");
        }

        if (isset($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (isset($criteria['has_media']) && $criteria['has_media']) {
            $query->has('media');
        }

        if (isset($criteria['sensitive']) && !$criteria['sensitive']) {
            $query->where('is_sensitive', false);
        }

        if (isset($criteria['date_from'])) {
            $query->where('created_at', '>=', $criteria['date_from']);
        }

        if (isset($criteria['date_to'])) {
            $query->where('created_at', '<=', $criteria['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * جستجوی پیشرفته مقالات
     */
    private function advancedArticleSearch(array $criteria, ?User $user): LengthAwarePaginator
    {
        $query = Article::with(['user', 'media'])
            ->published();

        if (isset($criteria['query'])) {
            $query->where(function ($q) use ($criteria) {
                $q->where('title', 'like', "%{$criteria['query']}%")
                    ->orWhere('content', 'like', "%{$criteria['query']}%");
            });
        }

        if (isset($criteria['tags'])) {
            foreach ($criteria['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        if (isset($criteria['approved']) && $criteria['approved']) {
            $query->where('is_approved', true);
        }

        if (isset($criteria['featured']) && $criteria['featured']) {
            $query->where('is_featured', true);
        }

        if (isset($criteria['min_views'])) {
            $query->where('view_count', '>=', $criteria['min_views']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($criteria['per_page'] ?? 10);
    }
}