<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, User $model): bool
    {
        // Public profiles
        if (!$model->is_private) {
            return true;
        }

        // Not logged in
        if (!$user) {
            return false;
        }

        // Own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Blocked by target user
        if ($model->hasBlocked($user->id)) {
            return false;
        }

        // Private account - only followers
        return $user->isFollowing($model->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole('admin');
    }

    public function follow(User $user, User $model): bool
    {
        return $user->id !== $model->id && !$model->hasBlocked($user->id);
    }

    public function block(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }

    public function mute(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }
}