<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConversationPolicy
{
    use HandlesAuthorization;

    /**
     * تعیین اینکه آیا کاربر می‌تواند مکالمه را مشاهده کند
     */
    public function view(User $user, Conversation $conversation): bool
    {
        // کاربر باید عضو مکالمه باشد
        return $conversation->isParticipant($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند در مکالمه پیام ارسال کند
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند شرکت کننده به مکالمه گروهی اضافه کند
     */
    public function addParticipant(User $user, Conversation $conversation): bool
    {
        // فقط ادمین‌های گروه یا سازنده گروه می‌توانند اضافه کنند
        if (!$conversation->isGroup()) {
            return false;
        }

        $pivot = $conversation->users()->where('user_id', $user->id)->first();
        return $pivot && ($pivot->pivot->role === 'admin' || $conversation->created_by === $user->id);
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مکالمه را ترک کند
     */
    public function leave(User $user, Conversation $conversation): bool
    {
        return $conversation->isParticipant($user) && $conversation->isGroup();
    }

    /**
     * تعیین اینکه آیا کاربر می‌تواند مکالمه را مدیریت کند (ادمین)
     */
    public function manage(User $user, Conversation $conversation): bool
    {
        return $user->isAdmin();
    }
}