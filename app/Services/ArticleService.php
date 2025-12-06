<?php

namespace App\Services;

use App\Models\Article;
use App\Models\ArticleMedia;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleService
{

    public function __construct(
        private NotificationService $notificationService
    ) {
    }


    /**
     * ایجاد مقاله جدید
     */
    public function createArticle(User $user, array $data): Article
    {
        return DB::transaction(function () use ($user, $data) {
            $article = Article::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'excerpt' => $data['excerpt'] ?? null,
                'content' => $data['content'],
                'status' => $data['status'] ?? 'draft',
                'tags' => $data['tags'] ?? [],
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);

            // آپلود تصویر شاخص
            if (isset($data['featured_image'])) {
                $article->update([
                    'featured_image' => $this->uploadFeaturedImage($data['featured_image'])
                ]);
            }

            // آپلود مدیا
            if (isset($data['media']) && is_array($data['media'])) {
                $this->uploadMedia($article, $data['media']);
            }

            // محاسبه زمان خواندن
            $article->calculateReadingTime();

            return $article->load('user', 'media');
        });
    }


    /**
     * آپدیت مقاله
     */
    public function updateArticle(Article $article, array $data): Article
    {
        return DB::transaction(function () use ($article, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = $data['title'];
                $updateData['slug'] = Str::slug($data['title']);
            }

            if (isset($data['content'])) {
                $updateData['content'] = $data['content'];
            }

            if (isset($data['excerpt'])) {
                $updateData['excerpt'] = $data['excerpt'];
            }

            if (isset($data['tags'])) {
                $updateData['tags'] = $data['tags'];
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
                if ($data['status'] === 'published' && !$article->published_at) {
                    $updateData['published_at'] = now();
                }
            }

            $article->update($updateData);

            // آپلود تصویر شاخص جدید
            if (isset($data['featured_image'])) {
                // حذف تصویر قبلی
                if ($article->featured_image) {
                    Storage::disk('public')->delete($article->featured_image);
                }
                $article->update([
                    'featured_image' => $this->uploadFeaturedImage($data['featured_image'])
                ]);
            }

            // محاسبه مجدد زمان خواندن
            $article->calculateReadingTime();

            return $article->load('user', 'media');
        });
    }

    /**
     * حذف مقاله
     */
    public function deleteArticle(Article $article): void
    {
        DB::transaction(function () use ($article) {
            // حذف تصویر شاخص
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }

            // حذف مدیاها
            foreach ($article->media as $media) {
                Storage::disk('public')->delete($media->file_path);
                $media->delete();
            }

            $article->delete();
        });
    }

    /**
     * دریافت مقالات
     */
    public function getArticles(?User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Article::with(['user', 'media'])
            ->published()
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس نویسنده
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // فیلتر بر اساس تگ
        if (isset($filters['tag'])) {
            $query->whereJsonContains('tags', $filters['tag']);
        }

        // فیلتر مقالات تأیید شده
        if (isset($filters['approved']) && $filters['approved']) {
            $query->where('is_approved', true);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * انتشار مقاله
     */
    public function publishArticle(Article $article): Article
    {
        $article->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        $article->calculateReadingTime();

        return $article->load('user', 'media');
    }

    /**
     * تأیید مقاله
     */
    public function approveArticle(Article $article, User $approver): Article
    {
        $article->approve($approver);
        return $article->load('user', 'media', 'approver');
    }



    /**
     * لایک/آنلایک مقاله
     */
    public function toggleLike(User $user, Article $article): bool
    {
        $like = $article->likes()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();
            $article->decrement('like_count');
            return false;
        } else {
            $article->likes()->create(['user_id' => $user->id]);
            $article->increment('like_count');

            // ارسال نوتیفیکیشن - فقط اگر کاربر، مالک مقاله نباشد
            if ($article->user_id !== $user->id) {
                try {
                    $this->notificationService->sendNewLikeNotification(
                        $article->user,
                        $user,
                        $article
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to send like notification for article: ' . $e->getMessage());
                }
            }

            return true;
        }
    }

    /**
     * بوکمارک/آنبوکمارک مقاله
     */
    public function toggleBookmark(User $user, Article $article): bool
    {
        $bookmark = $article->bookmarks()->where('user_id', $user->id)->first();

        if ($bookmark) {
            $bookmark->delete();
            return false;
        } else {
            $article->bookmarks()->create(['user_id' => $user->id]);
            return true;
        }
    }

    /**
     * دریافت مقالات کاربر
     */
    public function getUserArticles(int $userId, ?User $currentUser, array $filters = []): LengthAwarePaginator
    {
        $user = User::findOrFail($userId);

        $query = Article::with(['user', 'media'])
            ->where('user_id', $userId);

        // کاربران عادی فقط مقالات منتشر شده را می‌بینند
        if (!$currentUser || ($currentUser->id !== $userId && $currentUser->username !== 'admin')) {
            $query->published();
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * آپلود تصویر شاخص
     */
    private function uploadFeaturedImage($file): string
    {
        $uploadLimit = \App\Models\UploadLimit::getForType('article');

        if ($file->getSize() > $uploadLimit->max_file_size * 1024) {
            throw new \Exception("File size exceeds limit");
        }

        if (!$uploadLimit->isMimeAllowed($file->getMimeType())) {
            throw new \Exception("File type not allowed");
        }

        return $file->store('articles/featured', 'public');
    }

    /**
     * آپلود مدیا
     */
    private function uploadMedia(Article $article, array $mediaFiles): void
    {
        $uploadLimit = \App\Models\UploadLimit::getForType('article');

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
            $path = $file->store('articles/media', 'public');

            // ایجاد رکورد مدیا
            ArticleMedia::create([
                'article_id' => $article->id,
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
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } else {
            return 'document';
        }
    }
}