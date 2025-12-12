<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookmarkService
{
    /**
     * دریافت بوکمارک‌های کاربر
     */
    public function getUserBookmarks(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Bookmark::with(['bookmarkable'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس نوع
        if (isset($filters['type'])) {
            $modelClass = $this->getModelClass($filters['type']);
            $query->where('bookmarkable_type', $modelClass);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * حذف بوکمارک
     */
    public function removeBookmark(User $user, string $bookmarkableType, int $bookmarkableId): bool
    {
        $modelClass = $this->getModelClass($bookmarkableType);

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('bookmarkable_type', $modelClass)
            ->where('bookmarkable_id', $bookmarkableId)
            ->firstOrFail();

        return $bookmark->delete();
    }

    /**
     * افزودن بوکمارک
     */
    public function addBookmark(User $user, string $bookmarkableType, int $bookmarkableId): Bookmark
    {
        $modelClass = $this->getModelClass($bookmarkableType);
        $bookmarkable = $modelClass::findOrFail($bookmarkableId);

        // بررسی وجود بوکمارک
        $existingBookmark = Bookmark::where('user_id', $user->id)
            ->where('bookmarkable_type', $modelClass)
            ->where('bookmarkable_id', $bookmarkableId)
            ->first();

        if ($existingBookmark) {
            throw new \Exception('Already bookmarked');
        }

        return Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_type' => $modelClass,
            'bookmarkable_id' => $bookmarkableId,
        ]);
    }

    /**
     * تبدیل type به model class
     */
    private function getModelClass(string $type): string
    {
        return match ($type) {
            'post' => \App\Models\Post::class,
            default => throw new \Exception('Invalid bookmarkable type'),
        };
    }

    /**
     * بررسی وجود بوکمارک
     */
    public function isBookmarked(User $user, string $bookmarkableType, int $bookmarkableId): bool
    {
        $modelClass = $this->getModelClass($bookmarkableType);

        return Bookmark::where('user_id', $user->id)
            ->where('bookmarkable_type', $modelClass)
            ->where('bookmarkable_id', $bookmarkableId)
            ->exists();
    }

    /**
     * دریافت تعداد بوکمارک‌های کاربر
     */
    public function getBookmarksCount(User $user): array
    {
        return [
            'posts' => Bookmark::where('user_id', $user->id)
                ->where('bookmarkable_type', \App\Models\Post::class)
                ->count(),
            'total' => Bookmark::where('user_id', $user->id)->count(),
        ];
    }
}