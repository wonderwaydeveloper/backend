<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند پروفایل دیگران را مشاهده کند
     */
    public function view(User $currentUser, User $user): bool
    {
        // کاربر می‌تواند پروفایل خودش را ببیند
        if ($currentUser->id === $user->id) {
            return true;
        }

        // اگر کاربر مورد نظر خصوصی نباشد، همه می‌توانند ببینند
        if (!$user->is_private) {
            return true;
        }

        // اگر کاربر خصوصی است، فقط دنبال‌کنندگان تایید شده می‌توانند ببینند
        return $user->followers()
            ->where('follower_id', $currentUser->id)
            ->whereNotNull('approved_at')
            ->exists();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پروفایل را آپدیت کند
     */
    public function update(User $currentUser, User $user): bool
    {
        return $currentUser->id === $user->id;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کاربر دیگر را دنبال کند
     */
    public function follow(User $currentUser, User $user): bool
    {
        // کاربر نمی‌تواند خودش را دنبال کند
        if ($currentUser->id === $user->id) {
            return false;
        }

        // کاربر نمی‌تواند کاربران مسدود شده را دنبال کند
        if ($user->is_banned) {
            return false;
        }

        return true;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند درخواست دنبال کردن را بپذیرد/رد کند
     */
    public function manageFollowRequests(User $currentUser, User $user): bool
    {
        return $currentUser->id === $user->id && $user->is_private;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کاربران دیگر را مدیریت کند (ادمین)
     */
    public function manageUsers(User $currentUser): bool
    {
        return $currentUser->isAdmin();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کاربر دیگر را مسدود کند
     */
    public function ban(User $currentUser, User $user): bool
    {
        return $this->manageUsers($currentUser) && $currentUser->id !== $user->id;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کنترل والدین را مدیریت کند
     */

    // در app/Policies/UserPolicy.php
    public function manageParentalControls(User $currentUser, User $child): bool
    {
        // اگر کاربر ادمین باشد، اجازه دسترسی دارد
        if ($currentUser->isAdmin()) {
            return true;
        }

        // اگر کودک زیر سن نیست، اجازه نمی‌دهیم
        if (!$child->is_underage) {
            return false;
        }

        // اگر کودک والد ندارد، اجازه ایجاد کنترل والدین را می‌دهیم
        // (اولین کاربری که کنترل ایجاد کند، والد می‌شود)
        if (is_null($child->parent_id)) {
            return true;
        }

        // اگر کودک والد دارد، فقط همان والد می‌تواند مدیریت کند
        return $currentUser->id === $child->parent_id;
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند لیست کنترل‌های والدین را ببیند
     */
    public function viewParentalControls(User $currentUser): bool
    {
        // کاربر باید والد حداقل یک کودک باشد یا ادمین باشد
        return $currentUser->parentalControls()->exists() || $currentUser->isAdmin();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند کاربران زیر سن را مدیریت کند
     */
    public function viewUnderageUsers(User $currentUser): bool
    {
        return $this->manageUsers($currentUser);
    }
}