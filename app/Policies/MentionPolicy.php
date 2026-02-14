<?php

namespace App\Policies;

use App\Models\{Mention, User};

class MentionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('mention.view');
    }

    public function view(User $user, Mention $mention): bool
    {
        return $user->id === $mention->user_id || $user->hasPermissionTo('mention.view');
    }
}
