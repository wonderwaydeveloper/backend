<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArticlePolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را مشاهده کند
     */
    public function view(User $user, Article $article): bool
    {
        // مقالات حذف شده فقط توسط نویسنده یا ادمین قابل مشاهده هستند
        if ($article->trashed()) {
            return $user->id === $article->user_id || $this->manageArticles($user);
        }

        // مقالات پیش‌نویس فقط توسط نویسنده یا ادمین قابل مشاهده هستند
        if ($article->status === 'draft') {
            return $user->id === $article->user_id || $this->manageArticles($user);
        }

        // مقالات زمان‌بندی شده فقط توسط نویسنده یا ادمین قابل مشاهده هستند
        if ($article->status === 'scheduled' && $article->scheduled_at > now()) {
            return $user->id === $article->user_id || $this->manageArticles($user);
        }

        // مقالات منتشر شده باید تایید شده باشند (اگر نیاز به تایید داشته باشند)
        if ($article->status === 'published' && !$article->is_approved) {
            // اگر مقاله منتشر شده اما تایید نشده، همه می‌توانند ببینند
            // یا فقط نویسنده و ادمین
            return true; // همه می‌توانند ببینند
            // یا: return $user->id === $article->user_id || $this->manageArticles($user);
        }
        
        // اگر نویسنده مسدود شده است، فقط ادمین می‌تواند مقاله را ببیند
        if ($article->user->is_banned) {
            return $this->manageArticles($user);
        }

        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله ایجاد کند
     */
    public function create(User $user): bool
    {
        // کاربران مسدود شده نمی‌توانند مقاله ایجاد کنند
        if ($user->is_banned) {
            return false;
        }

        // کاربران زیر سن ممکن است محدودیت داشته باشند
        if ($user->is_underage) {
            // بررسی کنترل والدین
            return true; // به طور موقت true می‌گذاریم
        }

        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را آپدیت کند
     */
    public function update(User $user, Article $article): bool
    {
        // فقط نویسنده یا ادمین می‌توانند آپدیت کنند
        return $user->id === $article->user_id || $this->manageArticles($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را حذف کند
     */
    public function delete(User $user, Article $article): bool
    {
        return $user->id === $article->user_id || $this->manageArticles($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را منتشر کند
     */
    public function publish(User $user, Article $article): bool
    {
        // فقط نویسنده یا ادمین می‌توانند منتشر کنند
        if ($user->id !== $article->user_id && !$this->manageArticles($user)) {
            return false;
        }

        // مقالاتی که نیاز به تایید دارند فقط توسط ادمین قابل انتشار هستند
        if ($article->needs_approval && !$this->manageArticles($user)) {
            return false;
        }

        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را تایید کند
     */
    public function approve(User $user, Article $article): bool
    {
        return $this->manageArticles($user) && !$article->is_approved;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقالات را مدیریت کند (ادمین)
     */
    public function manageArticles(User $user): bool
    {
        return $user->isAdmin(); // استفاده از متد isAdmin که به مدل User اضافه کردیم
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مقاله را لایک کند
     */
    public function like(User $user, Article $article): bool
    {
        // کاربر نمی‌تواند مقاله خودش را لایک کند
        if ($user->id === $article->user_id) {
            return false;
        }

        // کاربران مسدود شده نمی‌توانند لایک کنند
        if ($user->is_banned) {
            return false;
        }

        return $this->view($user, $article);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند روی مقاله کامنت بگذارد
     */
    public function comment(User $user, Article $article): bool
    {
        // اگر کامنت‌ها بسته شده‌اند
        if ($article->comments_disabled ?? false) {
            return false;
        }

        // کاربران مسدود شده نمی‌توانند کامنت بگذارند
        if ($user->is_banned) {
            return false;
        }

        return $this->view($user, $article);
    }
}