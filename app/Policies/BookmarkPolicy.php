<?php

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookmarkPolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند بوکمارک‌ها را مشاهده کند
     * همه کاربران احراز هویت شده می‌توانند بوکمارک‌های خود را ببینند
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند بوکمارک ایجاد کند
     * همه کاربران احراز هویت شده می‌توانند بوکمارک ایجاد کنند
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند بوکمارک حذف کند
     * فقط صاحب بوکمارک می‌تواند آن را حذف کند
     */
    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $bookmark->user_id === $user->id;
    }
}