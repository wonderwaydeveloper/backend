<?php

namespace App\Policies;

use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrivateMessagePolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند پیام را مشاهده کند
     */
    public function view(User $user, PrivateMessage $message): bool
    {
        return $message->conversation->isParticipant($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پیام را حذف کند
     */
    public function delete(User $user, PrivateMessage $message): bool
    {
        // کاربر می‌تواند پیام خودش را حذف کند یا ادمین باشد
        return $user->id === $message->user_id || $user->isAdmin();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند پیام‌ها را مدیریت کند (ادمین)
     */
    public function manage(User $user, PrivateMessage $message): bool
    {
        return $user->isAdmin();
    }
}