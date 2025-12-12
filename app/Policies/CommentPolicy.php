<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\AuthorizationException;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت را مشاهده کند
     */
    public function view(User $user, Comment $comment): bool
    {
        // اگر کامنت حذف شده است، فقط نویسنده یا ادمین می‌تواند ببیند
        if ($comment->trashed()) {
            return $user->id === $comment->user_id || $this->manageComments($user);
        }

        // اگر نویسنده مسدود شده است، فقط ادمین می‌تواند کامنت را ببیند
        if ($comment->user->is_banned) {
            return $this->manageComments($user);
        }

        // کاربر باید به محتوای اصلی دسترسی داشته باشد
        return $this->canViewParent($user, $comment);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت ایجاد کند
     */
    public function create(User $user, $parentModel): bool
    {
        // کاربران مسدود شده نمی‌توانند کامنت ایجاد کنند
        if ($user->is_banned) {
            return false;
        }

        // کاربران زیر سن ممکن است محدودیت داشته باشند
        if ($user->is_underage) {
            // بررسی کنترل والدین
            // به طور موقت true می‌گذاریم، اما می‌توانید منطق خود را اضافه کنید
            return true;
        }

        // اگر parentModel وجود نداشته باشد، اجازه نمی‌دهیم
        if (!$parentModel) {
            return false;
        }

        // بررسی اینکه آیا کاربر به محتوای اصلی دسترسی دارد
        return $this->canViewParent($user, $parentModel);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت را آپدیت کند
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id && !$comment->trashed();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت را حذف کند
     */
    public function delete(User $user, Comment $comment): bool
    {
        // نویسنده یا ادمین می‌توانند حذف کنند
        return $user->id === $comment->user_id || $this->manageComments($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت را لایک کند
     */

    // اصلاح متد like در CommentPolicy.php
    public function like(User $user, Comment $comment): bool
    {
        // کاربر نمی‌تواند کامنت خودش را لایک کند
        if ($user->id === $comment->user_id) {
            throw new AuthorizationException('You cannot like your own comment');
        }

        // کاربران مسدود شده نمی‌توانند لایک کنند
        if ($user->is_banned) {
            return false;
        }

        // کاربر باید بتواند کامنت را ببیند
        return $this->view($user, $comment);
    }


    /**
     * تعیین اینکه آیا کاربر می‌تواند پاسخ به کامنت ایجاد کند
     */
    public function reply(User $user, Comment $comment): bool
    {
        return $this->create($user, $comment->commentable) && !$comment->trashed();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کامنت‌ها را مدیریت کند (ادمین)
     */
    public function manageComments(User $user): bool
    {
        return $user->isAdmin(); // استفاده از متد isAdmin که به مدل User اضافه کردیم
    }

    /**
     * بررسی دسترسی به محتوای والد
     */
    private function canViewParent(User $user, $model): bool
    {
        if ($model instanceof Comment) {
            return $this->canViewParent($user, $model->commentable);
        }

        // بررسی دسترسی به پست‌ها
        if ($model instanceof \App\Models\Post) {
            // اگر نویسنده مسدود شده است، فقط ادمین می‌تواند پست را ببیند
            if ($model->user->is_banned) {
                return $this->manageComments($user);
            }

            // اگر پست حذف شده است، فقط نویسنده یا ادمین می‌تواند ببیند
            if ($model->trashed()) {
                return $user->id === $model->user_id || $this->manageComments($user);
            }

            // اگر نویسنده خصوصی است، فقط دنبال‌کنندگان تایید شده می‌توانند ببینند
            if ($model->user->is_private) {
                // نویسنده همیشه می‌تواند پست خودش را ببیند
                if ($model->user_id === $user->id) {
                    return true;
                }

                // بررسی اینکه آیا کاربر از دنبال‌کنندگان تایید شده است
                return $model->user->followers()
                    ->where('follower_id', $user->id)
                    ->whereNotNull('approved_at')
                    ->exists();
            }

            // بررسی محدودیت‌های سنی برای محتوای حساس
            if ($model->is_sensitive && $user->is_underage) {
                return false;
            }

            return true;
        }

        return true; // به طور پیش‌فرض اجازه دسترسی می‌دهیم
    }
}