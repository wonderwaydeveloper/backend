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
        // مهم: همیشه آخرین اطلاعات کاربر را از پایگاه داده بخوان
        $postOwner = User::find($post->user_id);

        // اگر نویسنده مسدود شده است، فقط ادمین می‌تواند پست را ببیند
        if ($postOwner->is_banned) {
            return $this->managePosts($user);
        }

        // اگر پست حذف شده است، فقط نویسنده یا ادمین می‌تواند ببیند
        if ($post->trashed()) {
            return $user->id === $post->user_id || $this->managePosts($user);
        }

        // اگر نویسنده خصوصی است، فقط دنبال‌کنندگان تایید شده می‌توانند ببینند
        if ($postOwner->is_private) {
            // نویسنده همیشه می‌تواند پست خودش را ببیند
            if ($post->user_id === $user->id) {
                return true;
            }

            // بررسی اینکه آیا کاربر از دنبال‌کنندگان تایید شده است
            return $postOwner->followers()
                ->where('follower_id', $user->id)
                ->whereNotNull('approved_at')
                ->exists();
        }

        // بررسی محدودیت‌های سنی برای محتوای حساس
        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

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