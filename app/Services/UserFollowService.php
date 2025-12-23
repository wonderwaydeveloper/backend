<?php

namespace App\Services;

use App\Models\User;

class UserFollowService
{
    public function follow(int $userId, int $targetUserId): bool
    {
        $user = User::findOrFail($userId);
        
        if (!$user->following()->where('following_id', $targetUserId)->exists()) {
            $user->following()->attach($targetUserId);
            return true;
        }
        return false;
    }

    public function unfollow(int $userId, int $targetUserId): bool
    {
        $user = User::findOrFail($userId);
        return $user->following()->detach($targetUserId) > 0;
    }

    public function getFollowers(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = User::findOrFail($userId);
        return $user->followers()->paginate(20);
    }

    public function getFollowing(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = User::findOrFail($userId);
        return $user->following()->paginate(20);
    }
}