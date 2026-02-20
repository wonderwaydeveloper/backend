<?php

namespace App\Services;

use App\Models\User;
use App\Events\UserFollowed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserFollowService
{
    public function follow(int $userId, int $targetUserId): bool
    {
        // Prevent self-follow
        if ($userId === $targetUserId) {
            throw new \InvalidArgumentException('Cannot follow yourself');
        }
        
        try {
            return DB::transaction(function () use ($userId, $targetUserId) {
                $user = User::lockForUpdate()->findOrFail($userId);
                $targetUser = User::lockForUpdate()->findOrFail($targetUserId);
                
                // Check if already following
                if ($user->following()->where('following_id', $targetUserId)->exists()) {
                    return false;
                }
                
                // Create follow relationship
                $user->following()->attach($targetUserId);
                
                // Update counters atomically
                $user->increment('following_count');
                $targetUser->increment('followers_count');
                
                // Dispatch follow event for notifications
                event(new UserFollowed($targetUser, $user));
                
                return true;
            });
        } catch (\Exception $e) {
            Log::error('Follow failed', [
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function unfollow(int $userId, int $targetUserId): bool
    {
        try {
            return DB::transaction(function () use ($userId, $targetUserId) {
                $user = User::lockForUpdate()->findOrFail($userId);
                $targetUser = User::lockForUpdate()->findOrFail($targetUserId);
                
                // Remove follow relationship
                $detached = $user->following()->detach($targetUserId);
                
                if ($detached > 0) {
                    // Update counters atomically (prevent underflow)
                    if ($user->following_count > 0) {
                        $user->decrement('following_count');
                    }
                    if ($targetUser->followers_count > 0) {
                        $targetUser->decrement('followers_count');
                    }
                    return true;
                }
                
                return false;
            });
        } catch (\Exception $e) {
            Log::error('Unfollow failed', [
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
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