<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را مشاهده کند
     */


    public function view(User $user, Post $post): bool
    {
        // اگر پست حذف شده، فقط مالک یا ادمین می‌توانند ببینند
        if ($post->trashed()) {
            return $user->id === $post->user_id || $user->isAdmin();
        }

        // اگر کاربر مسدود شده، فقط ادمین می‌تواند ببیند
        if ($post->user->is_banned && !$user->isAdmin()) {
            return false;
        }

        // اگر پست خصوصی است، فقط مالک یا دنبال‌کنندگان تایید شده می‌توانند ببینند
        if ($post->user->is_private) {
            if ($user->id === $post->user_id) {
                return true;
            }

            return $post->user->followers()
                ->where('follower_id', $user->id)
                ->whereNotNull('approved_at')
                ->exists();
        }

        // اگر محتوای حساس و کاربر زیر سن است
        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        // بقیه موارد
        return true;
    }


    /**
     * تعیین اینکه آیا کاربر می‌تواند پست ایجاد کند
     */
    public function create(User $user): bool
    {
        // کاربران مسدود شده نمی‌توانند پست ایجاد کنند
        if ($user->is_banned) {
            return false;
        }

        // کاربران زیر سن ممکن است محدودیت داشته باشند
        if ($user->is_underage) {
            // اینجا می‌توانید منطق بررسی کنترل والدین را اضافه کنید
            return true; // به طور موقت true می‌گذاریم
        }

        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را آپدیت کند
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && !$post->trashed();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را حذف کند
     */
    public function delete(User $user, Post $post): bool
    {
        // نویسنده می‌تواند حذف کند یا ادمین
        return $user->id === $post->user_id || $this->managePosts($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را بازنشر کند
     */
    public function repost(User $user, Post $post): bool
    {
        // کاربر نمی‌تواند پست خودش را بازنشر کند
        if ($user->id === $post->user_id) {
            return false;
        }

        // کاربران مسدود شده نمی‌توانند بازنشر کنند
        if ($user->is_banned) {
            return false;
        }

        // بررسی محدودیت‌های سنی برای محتوای حساس
        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        return $this->view($user, $post);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را لایک کند
     */
    public function like(User $user, Post $post): bool
    {
        // کاربران مسدود شده نمی‌توانند لایک کنند
        if ($user->is_banned) {
            return false;
        }

        // **مهمه: کاربر نمی‌تواند پست خودش را لایک کند**
        if ($user->id === $post->user_id) {
            return false;
        }

        // بررسی محدودیت‌های سنی برای محتوای حساس
        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        // در نهایت، کاربر باید بتواند پست را ببیند تا بتواند آن را لایک کند
        return $this->view($user, $post);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند روی پست کامنت بگذارد
     */
    public function comment(User $user, Post $post): bool
    {
        // اگر کامنت‌ها بسته شده‌اند
        if ($post->comments_disabled ?? false) {
            return false;
        }

        // کاربران مسدود شده نمی‌توانند کامنت بگذارند
        if ($user->is_banned) {
            return false;
        }

        return $this->view($user, $post);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست‌ها را مدیریت کند (ادمین)
     */
    public function managePosts(User $user): bool
    {
        return $user->isAdmin(); // استفاده از متد isAdmin که به مدل User اضافه کردیم
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند محتوای حساس ایجاد کند
     */
    public function createSensitiveContent(User $user): bool
    {
        // کاربران زیر سن نمی‌توانند محتوای حساس ایجاد کنند
        if ($user->is_underage) {
            return false;
        }

        return true;
    }
}