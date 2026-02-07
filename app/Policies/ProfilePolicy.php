<?php

namespace App\Policies;

use App\Models\User;

class ProfilePolicy
{
    public function view(?User $currentUser, User $profileUser): bool
    {
        // Public profiles
        if (!$profileUser->is_private) {
            return true;
        }

        // Not logged in
        if (!$currentUser) {
            return false;
        }

        // Own profile
        if ($currentUser->id === $profileUser->id) {
            return true;
        }

        // Blocked
        if (in_array($currentUser->id, $profileUser->blocked_users ?? [])) {
            return false;
        }

        // Private account - only followers
        return $currentUser->isFollowing($profileUser->id);
    }

    public function update(User $user, User $profile): bool
    {
        return $user->id === $profile->id;
    }

    public function follow(User $user, User $targetUser): bool
    {
        // Can't follow yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Can't follow if blocked
        if (in_array($user->id, $targetUser->blocked_users ?? [])) {
            return false;
        }

        return true;
    }

    public function block(User $user, User $targetUser): bool
    {
        return $user->id !== $targetUser->id;
    }

    public function mute(User $user, User $targetUser): bool
    {
        return $user->id !== $targetUser->id;
    }
}
