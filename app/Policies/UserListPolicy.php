<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserList;

class UserListPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserList $list): bool
    {
        // Public lists
        if ($list->is_public) {
            return true;
        }

        // Own list
        return $user->id === $list->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, UserList $list): bool
    {
        return $user->id === $list->user_id;
    }

    public function delete(User $user, UserList $list): bool
    {
        return $user->id === $list->user_id;
    }

    public function addMember(User $user, UserList $list): bool
    {
        return $user->id === $list->user_id;
    }

    public function removeMember(User $user, UserList $list): bool
    {
        return $user->id === $list->user_id;
    }
}
