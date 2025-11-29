<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

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
            return true; // به طور موقت true می‌گذاریم
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
    public function like(User $user, Comment $comment): bool
    {
        // کاربر نمی‌تواند کامنت خودش را لایک کند
        if ($user->id === $comment->user_id) {
            return false;
        }

        // کاربران مسدود شده نمی‌توانند لایک کنند
        if ($user->is_banned) {
            return false;
        }

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
        return $user->username === 'admin'; // مثال ساده
    }

    /**
     * بررسی دسترسی به محتوای والد
     */
    private function canViewParent(User $user, $model): bool
    {
        if ($model instanceof Comment) {
            return $this->canViewParent($user, $model->commentable);
        }

        // استفاده از پالیسی مربوط به مدل والد
        if (method_exists($model, 'policy') && policy($model)->view($user, $model)) {
            return true;
        }

        return false;
    }
}