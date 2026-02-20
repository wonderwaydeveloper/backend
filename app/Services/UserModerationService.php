<?php

namespace App\Services;

use App\Events\{UserBlocked, UserMuted};
use App\Models\{Block, Mute, User};

class UserModerationService
{
    public function blockUser(User $user, User $targetUser, ?string $reason = null): array
    {
        Block::firstOrCreate(
            [
                'blocker_id' => $user->id,
                'blocked_id' => $targetUser->id
            ],
            ['reason' => $reason]
        );

        // Auto-unfollow with counter updates (prevent underflow)
        if ($user->following()->where('following_id', $targetUser->id)->exists()) {
            $user->following()->detach($targetUser->id);
            if ($user->following_count > 0) {
                $user->decrement('following_count');
            }
            if ($targetUser->followers_count > 0) {
                $targetUser->decrement('followers_count');
            }
        }
        
        if ($targetUser->following()->where('following_id', $user->id)->exists()) {
            $targetUser->following()->detach($user->id);
            if ($targetUser->following_count > 0) {
                $targetUser->decrement('following_count');
            }
            if ($user->followers_count > 0) {
                $user->decrement('followers_count');
            }
        }

        event(new UserBlocked($user, $targetUser));

        return [
            'message' => 'User blocked successfully',
            'blocked_user' => $targetUser->only(['id', 'name', 'username'])
        ];
    }

    public function unblockUser(User $user, User $targetUser): array
    {
        Block::where('blocker_id', $user->id)
            ->where('blocked_id', $targetUser->id)
            ->delete();

        return [
            'message' => 'User unblocked successfully',
            'unblocked_user' => $targetUser->only(['id', 'name', 'username'])
        ];
    }

    public function muteUser(User $user, User $targetUser, ?string $expiresAt = null): array
    {
        Mute::firstOrCreate(
            [
                'muter_id' => $user->id,
                'muted_id' => $targetUser->id
            ],
            ['expires_at' => $expiresAt]
        );

        event(new UserMuted($user, $targetUser));

        return [
            'message' => 'User muted successfully',
            'muted_user' => $targetUser->only(['id', 'name', 'username'])
        ];
    }

    public function unmuteUser(User $user, User $targetUser): array
    {
        Mute::where('muter_id', $user->id)
            ->where('muted_id', $targetUser->id)
            ->delete();

        return [
            'message' => 'User unmuted successfully',
            'unmuted_user' => $targetUser->only(['id', 'name', 'username'])
        ];
    }
}