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

        // Auto-unfollow
        $user->following()->detach($targetUser->id);
        $targetUser->following()->detach($user->id);

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