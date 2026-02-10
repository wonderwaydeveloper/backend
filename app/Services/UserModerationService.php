<?php

namespace App\Services;

use App\Events\{UserBlocked, UserMuted};
use App\Models\{Block, Mute, User};

class UserModerationService
{
    public function blockUser(User $user, User $targetUser): array
    {
        Block::firstOrCreate([
            'blocker_id' => $user->id,
            'blocked_id' => $targetUser->id
        ]);

        // Auto-unfollow with counter updates
        if ($user->following()->where('following_id', $targetUser->id)->exists()) {
            $user->following()->detach($targetUser->id);
            $user->decrement('following_count');
            $targetUser->decrement('followers_count');
        }
        
        if ($targetUser->following()->where('following_id', $user->id)->exists()) {
            $targetUser->following()->detach($user->id);
            $targetUser->decrement('following_count');
            $user->decrement('followers_count');
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

    public function muteUser(User $user, User $targetUser): array
    {
        Mute::firstOrCreate([
            'muter_id' => $user->id,
            'muted_id' => $targetUser->id
        ]);

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