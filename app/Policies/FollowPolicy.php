<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FollowRequest;

class FollowPolicy
{
    public function follow(User $user, User $targetUser): bool
    {
        // Can't follow yourself
        if ($user->id === $targetUser->id) {
            return false;
        }

        // Can't follow if blocked
        if ($targetUser->hasBlocked($user->id)) {
            return false;
        }

        // Can't follow if already following
        if ($user->isFollowing($targetUser->id)) {
            return false;
        }

        return true;
    }

    public function unfollow(User $user, User $targetUser): bool
    {
        return $user->isFollowing($targetUser->id);
    }

    public function accept(User $user, FollowRequest $followRequest): bool
    {
        return $user->id === $followRequest->following_id;
    }

    public function reject(User $user, FollowRequest $followRequest): bool
    {
        return $user->id === $followRequest->following_id;
    }

    public function viewFollowers(User $currentUser, User $targetUser): bool
    {
        // Own followers
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // Public account
        if (!$targetUser->is_private) {
            return true;
        }

        // Private account - only followers
        return $currentUser->isFollowing($targetUser->id);
    }

    public function viewFollowing(User $currentUser, User $targetUser): bool
    {
        return $this->viewFollowers($currentUser, $targetUser);
    }
}
