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
        private RedisService $redisService,
        private NotificationService $notificationService
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
    public function getPosts($userId = null, array $filters = [])
    {
        // رفع مشکل فراخوانی Redis
        $cacheKey = $userId ? "user_feed:{$userId}" : "public_posts";

        try {
            // استفاده از تابع اصلاح شده
            $cachedPosts = $this->redisService->getCachedPosts();

            if ($cachedPosts) {
                return $cachedPosts;
            }
        } catch (\Exception $e) {
            \Log::warning('Redis cache failed: ' . $e->getMessage());
        }

        // منطق اصلی گرفتن پست‌ها
        $query = Post::with(['user', 'media'])
            ->where('type', 'post')
            ->orderBy('created_at', 'desc');

        if (!empty($filters)) {
            $query = $this->applyFilters($query, $filters);
        }

        $posts = $query->paginate(15);

        // کش کردن نتیجه
        try {
            $this->redisService->cachePosts($posts);
        } catch (\Exception $e) {
            // خطای کش کردن رو نادیده بگیر
        }

        return $posts;
    }

    public function applyFilters($query, array $filters)
    {
        // منطق فیلتر کردن
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query;
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

        // 1. بررسی کش (باید در تست به گونه‌ای mock شود که false برگرداند)
        $cached = $this->redisService->getCachedUserFeed($cacheKey);
        if ($cached) {
            // اگر داده‌های کش شده آرایه‌ای از IDها هستند، آنها را به مدل Post تبدیل کنید
            $postIds = collect($cached)->pluck('id')->toArray();
            $posts = Post::with(['user', 'media'])
                ->whereIn('id', $postIds)
                ->orderByRaw('FIELD(id, ' . implode(',', $postIds) . ')')
                ->paginate($filters['per_page'] ?? 20);
            return $posts;
        }

        // 2. دریافت ID کاربران دنبال شده
        // استفاده از pluck('id') کافی است و استانداردتر است
        $followingIds =  $user->following()->pluck('users.id');

        // 3. اضافه کردن ID کاربر فعلی به لیست و اطمینان از یکتا بودن
        $userIdsForFeed = $followingIds->push($user->id)->unique();

        // 4. ساخت کوئری اصلی
        $query = Post::with(['user', 'media'])
            ->whereIn('user_id', $userIdsForFeed)
            ->published() // اطمینان از اینکه این اسکوپ در مدل Post به درستی تعریف شده
            ->orderBy('created_at', 'desc');

        // 5. اعمال فیلتر محتوای حساس برای کاربران زیر سن
        if ($user->is_underage) {
            $query->where('is_sensitive', false);
        }

        // 6. اجرای کوئری و صفحه‌بندی
        $posts = $query->paginate($filters['per_page'] ?? 20);

        // 7. کش کردن نتایج (فقط IDها برای صرفه‌جویی در حافظه)
        $this->redisService->cacheUserFeed($cacheKey, $posts->pluck('id')->toArray(), 300);

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
        try {
            $like = $post->likes()->where('user_id', $user->id)->first();

            if ($like) {
                $like->delete();
                $post->decrement('like_count');
                return false;
            } else {
                $post->likes()->create(['user_id' => $user->id]);
                $post->increment('like_count');

                // ارسال نوتیفیکیشن - فقط اگر کاربر، مالک پست نباشد
                if ($post->user_id !== $user->id) {
                    try {
                        $this->notificationService->sendNewLikeNotification(
                            $post->user,
                            $user,
                            $post
                        );
                    } catch (\Exception $e) {
                        \Log::error('Failed to send like notification: ' . $e->getMessage());
                        // خطا را throw نکنید، فقط لاگ کنید
                    }
                }

                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Error in toggleLike: ' . $e->getMessage());
            throw $e; // خطا را دوباره throw کنید تا controller آن را بگیرد
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
            // به جای حذف بوکمارک، استثنا پرتاب کن
            throw new \Exception('Already bookmarked');
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