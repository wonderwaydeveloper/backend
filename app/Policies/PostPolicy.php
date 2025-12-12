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
     * این متد اصلی برای کنترل دسترسی مشاهده پست است
     */
    public function view(User $user, Post $post): bool
    {
        //1. اگر پست حذف شده باشد، فقط نویسنده یا ادمین می‌توانند ببینند
        if ($post->trashed()) {
            return $user->id === $post->user_id || $user->isAdmin();
        }

        //2. کاربر همیشه می‌تواند پست خود را ببیند (حتی اگر مسدود شده باشد)
        if ($user->id === $post->user_id) {
            return true;
        }

        //3. بررسی مسدود بودن صاحب پست:اگر صاحب پست مسدود باشد، فقط ادمین می‌تواند پست را ببیند 
        if ($post->user->is_banned && !$user->isAdmin()) {
            return false;
        }

        //4. بررسی دسترسی به محتوای کاربر صاحب پست
        if (!$this->canAccessUserContent($user, $post->user)) {
            return false;
        }

        //5. اگر پست خصوصی باشد و صاحب پست عمومی باشد، فقط دنبال‌کنندگان تایید شده
        if ($post->is_private && !$post->user->is_private) {
            if (!$this->isApprovedFollower($user, $post->user)) {
                return false;
            }
        }

        //6. اگر پست حساس باشد و کاربر زیر سن باشد، اجازه مشاهده ندارد
        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        // در غیر این صورت اجازه مشاهده داده می‌شود
        return true;
    }

    /**
     * بررسی دسترسی کاربر به محتوای یک کاربر دیگر
     * این متد کمکی برای ساده‌سازی منطق دسترسی است
     */
    protected function canAccessUserContent(User $viewer, User $contentOwner): bool
    {
        // کاربر همیشه به محتوای خودش دسترسی دارد
        if ($viewer->id === $contentOwner->id) {
            return true;
        }

        // اگر کاربر صاحب محتوا خصوصی باشد
        if ($contentOwner->is_private) {
            // فقط دنبال‌کنندگان تایید شده می‌توانند محتوا را ببینند
            return $this->isApprovedFollower($viewer, $contentOwner);
        }

        // اگر کاربر صاحب محتوا عمومی باشد، همه می‌توانند محتوا را ببینند
        return true;
    }

    /**
     * بررسی اینکه آیا یک کاربر، دنبال‌کننده تایید شده کاربر دیگر است یا نه
     */
    protected function isApprovedFollower(User $follower, User $following): bool
    {
        return $following->followers()
            ->where('follower_id', $follower->id)
            ->whereNotNull('approved_at')
            ->exists();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست جدید ایجاد کند
     * کاربران مسدود شده نمی‌توانند پست ایجاد کنند
     */
    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را ویرایش کند
     * فقط نویسنده پست می‌تواند آن را ویرایش کند و پست نباید حذف شده باشد
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && !$post->trashed();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را حذف کند
     * نویسنده پست یا ادمین می‌توانند پست را حذف کنند
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id || $user->isAdmin();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را بازنشر کند
     * کاربر نمی‌تواند پست خودش را بازنشر کند
     * کاربران مسدود شده نمی‌توانند بازنشر کنند
     * کاربران زیر سن نمی‌توانند محتوای حساس را بازنشر کنند
     * کاربر باید بتواند پست را ببیند تا بتواند آن را بازنشر کند
     */
    public function repost(User $user, Post $post): bool
    {
        if ($user->id === $post->user_id) {
            return false;
        }

        if ($user->is_banned) {
            return false;
        }

        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        return $this->view($user, $post);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست را لایک کند
     * کاربر نمی‌تواند پست خودش را لایک کند
     * کاربران مسدود شده نمی‌توانند لایک کنند
     * کاربران زیر سن نمی‌توانند محتوای حساس را لایک کنند
     * کاربر باید بتواند پست را ببیند تا بتواند آن را لایک کند
     */
    public function like(User $user, Post $post): bool
    {
        if ($user->is_banned) {
            return false;
        }

        if ($user->id === $post->user_id) {
            return false;
        }

        if ($post->is_sensitive && $user->is_underage) {
            return false;
        }

        return $this->view($user, $post);
    }


    /**
     * تعیین اینکه آیا کاربر می‌تواند روی پست کامنت بگذارد
     * اگر کامنت‌ها غیرفعال شده باشند، کاربران عادی نمی‌توانند کامنت بگذارند
     * اما صاحب پست و ادمین همچنان می‌توانند کامنت بگذارند
     * کاربران مسدود شده تحت هیچ شرایطی نمی‌توانند کامنت بگذارند
     */
    public function comment(User $user, Post $post): bool
    {
        // 1. کاربران مسدود شده نمی‌توانند کامنت بگذارند (حتی روی پست خودشان)
        if ($user->is_banned) {
            return false;
        }

        // 2. بررسی غیرفعال بودن کامنت‌ها
        if ($post->comments_disabled) {
            // اگر کامنت‌ها غیرفعال باشد، فقط صاحب پست یا ادمین می‌توانند کامنت بگذارند
            return ($user->id === $post->user_id || $user->isAdmin()) && $this->view($user, $post);
        }

        // 3. اگر کامنت‌ها فعال باشد، کاربر باید بتواند پست را ببیند تا بتواند کامنت بگذارد
        return $this->view($user, $post);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پست‌ها را مدیریت کند (ادمین)
     * این متد برای دسترسی‌های سطح ادمین استفاده می‌شود
     */
    public function managePosts(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند محتوای حساس ایجاد کند
     * کاربران زیر سن نمی‌توانند محتوای حساس ایجاد کنند
     */
    public function createSensitiveContent(User $user): bool
    {
        return !$user->is_underage;
    }
}