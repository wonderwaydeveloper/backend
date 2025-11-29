<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function __construct(
        private RedisService $redisService
    ) {
    }

    /**
     * ایجاد پست جدید
     */
    public function createPost(User $user, array $data): Post
    {
        return DB::transaction(function () use ($user, $data) {
            $post = Post::create([
                'user_id' => $user->id,
                'content' => $data['content'],
                'type' => $data['type'] ?? 'post',
                'parent_id' => $data['parent_id'] ?? null,
                'original_post_id' => $data['original_post_id'] ?? null,
                'is_sensitive' => $data['is_sensitive'] ?? false,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'published_at' => $data['scheduled_at'] ?? now(),
            ]);

            // آپلود مدیا
            if (isset($data['media']) && is_array($data['media'])) {
                $this->uploadMedia($post, $data['media']);
            }

            // آپدیت تعداد پست‌های کاربر
            $user->increment('posts_count');

            return $post->load('user', 'media');
        });
    }

    /**
     * آپدیت پست
     */
    public function updatePost(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            $post->update([
                'content' => $data['content'],
                'is_sensitive' => $data['is_sensitive'] ?? $post->is_sensitive,
                'is_edited' => true,
            ]);

            return $post->load('user', 'media');
        });
    }

    /**
     * حذف پست
     */
    public function deletePost(Post $post): void
    {
        DB::transaction(function () use ($post) {
            // حذف مدیاها
            foreach ($post->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                if ($media->thumbnail) {
                    Storage::disk('public')->delete($media->thumbnail);
                }
                $media->delete();
            }

            // آپدیت تعداد پست‌های کاربر
            $post->user->decrement('posts_count');

            $post->delete();
        });
    }

    /**
     * دریافت پست‌ها با کش
     */
    public function getPosts(?User $user, array $filters = []): LengthAwarePaginator
    {
        $cacheKey = $this->generateCacheKey('posts', $filters, $user);

        // بررسی کش
        $cached = $this->redisService->getCachedUserFeed($cacheKey);
        if ($cached) {
            return $this->paginateFromCache($cached, $filters);
        }

        $query = Post::with(['user', 'media', 'parent', 'originalPost'])
            ->published()
            ->orderBy('created_at', 'desc');

        // فیلترها
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($user && $user->is_underage) {
            $query->where('is_sensitive', false);
        }

        $posts = $query->paginate($filters['per_page'] ?? 15);

        // کش کردن نتایج
        $this->redisService->cacheUserFeed($cacheKey, $posts->items(), 300);

        return $posts;
    }

    /**
     * افزایش بازدید پست با کش
     */
    public function incrementViewCount(Post $post): void
    {
        // افزایش در Redis
        $viewCount = $this->redisService->incrementPostView($post->id);

        // هر 10 بازدید، دیتابیس را آپدیت کن
        if ($viewCount % 10 === 0) {
            $post->update(['view_count' => $viewCount]);
        }
    }

    /**
     * دریافت تعداد بازدید پست
     */
    public function getPostViewCount(Post $post): int
    {
        $redisViews = $this->redisService->getPostViews($post->id);
        return max($redisViews, $post->view_count);
    }



    /**
     * دریافت پست‌های کاربر
     */
    public function getUserPosts(int $userId, ?User $currentUser, array $filters = []): LengthAwarePaginator
    {
        $user = User::findOrFail($userId);

        // بررسی دسترسی به پست‌های کاربر خصوصی
        if ($user->is_private && $currentUser && !$user->followers()->where('follower_id', $currentUser->id)->exists()) {
            throw new \Exception('Cannot view posts of private user');
        }

        return $this->getPosts($currentUser, array_merge($filters, ['user_id' => $userId]));
    }

    /**
     * دریافت فید کاربر با کش
     */
    public function getUserFeed(User $user, array $filters = []): LengthAwarePaginator
    {
        $cacheKey = "user_feed:{$user->id}:" . md5(json_encode($filters));

        $cached = $this->redisService->getCachedUserFeed($cacheKey);
        if ($cached) {
            return $this->paginateFromCache($cached, $filters);
        }

        $followingIds = $user->following()->pluck('users.id');

        $query = Post::with(['user', 'media'])
            ->whereIn('user_id', $followingIds->push($user->id))
            ->published()
            ->orderBy('created_at', 'desc');

        if ($user->is_underage) {
            $query->where('is_sensitive', false);
        }

        $posts = $query->paginate($filters['per_page'] ?? 20);

        // کش کردن
        $this->redisService->cacheUserFeed($cacheKey, $posts->items(), 300);

        return $posts;
    }

    /**
     * تولید کلید کش
     */
    private function generateCacheKey(string $type, array $filters, ?User $user = null): string
    {
        $userId = $user ? $user->id : 'guest';
        return "{$type}:" . md5($userId . json_encode($filters));
    }

    /**
     * ایجاد pagination از داده‌های کش شده
     */
    private function paginateFromCache(array $items, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        $offset = ($page - 1) * $perPage;
        $paginatedItems = array_slice($items, $offset, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            count($items),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
    }


    /**
     * لایک/آنلایک پست
     */
    public function toggleLike(User $user, Post $post): bool
    {
        $like = $post->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $post->decrement('like_count');
            return false;
        } else {
            $post->likes()->create(['user_id' => $user->id]);
            $post->increment('like_count');
            return true;
        }
    }

    /**
     * بازنشر پست
     */
    public function repost(User $user, Post $post): Post
    {
        return DB::transaction(function () use ($user, $post) {
            $repost = Post::create([
                'user_id' => $user->id,
                'type' => 'quote',
                'original_post_id' => $post->id,
                'published_at' => now(),
            ]);

            $post->increment('repost_count');

            return $repost;
        });
    }

    /**
     * بوکمارک/آنبوکمارک پست
     */
    public function toggleBookmark(User $user, Post $post): bool
    {
        $bookmark = $post->bookmarks()->where('user_id', $user->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            return false;
        } else {
            $post->bookmarks()->create(['user_id' => $user->id]);
            return true;
        }
    }

    /**
     * آپلود مدیا
     */
    private function uploadMedia(Post $post, array $mediaFiles): void
    {
        $uploadLimit = \App\Models\UploadLimit::getForType('post');

        if (count($mediaFiles) > $uploadLimit->max_files) {
            throw new \Exception("Maximum {$uploadLimit->max_files} files allowed");
        }

        $totalSize = 0;
        $mediaCount = 0;

        foreach ($mediaFiles as $file) {
            if ($mediaCount >= $uploadLimit->max_files) {
                break;
            }

            // بررسی نوع فایل
            if (!$uploadLimit->isMimeAllowed($file->getMimeType())) {
                continue;
            }

            // بررسی حجم فایل
            if ($file->getSize() > $uploadLimit->max_file_size * 1024) {
                continue;
            }

            $totalSize += $file->getSize();
            if ($totalSize > $uploadLimit->max_total_size * 1024) {
                break;
            }

            // آپلود فایل
            $path = $file->store('posts/media', 'public');

            // ایجاد رکورد مدیا
            PostMedia::create([
                'post_id' => $post->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'type' => $this->getMediaType($file->getMimeType()),
                'order' => $mediaCount,
            ]);

            $mediaCount++;
        }
    }

    /**
     * تشخیص نوع مدیا
     */
    private function getMediaType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return $mimeType === 'image/gif' ? 'gif' : 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } else {
            return 'file';
        }
    }
}