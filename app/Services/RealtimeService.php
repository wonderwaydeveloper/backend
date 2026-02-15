<?php

namespace App\Services;

use App\Events\UserOnlineStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RealtimeService
{
    public function updateUserStatus(User $user, string $status): array
    {
        $isOnline = $status === 'online';

        DB::transaction(function () use ($user, $isOnline) {
            $user->update([
                'last_seen_at' => now(),
                'is_online' => $isOnline,
            ]);
        });

        broadcast(new UserOnlineStatus($user->id, $status));

        Cache::tags(['online-users'])->forget('online-users-list');

        return [
            'status' => 'updated',
            'user_id' => $user->id,
            'is_online' => $isOnline,
            'last_seen_at' => $user->last_seen_at,
        ];
    }

    public function getOnlineUsers(): array
    {
        return Cache::tags(['online-users'])->remember('online-users-list', 60, function () {
            return User::where('is_online', true)
                ->where('last_seen_at', '>', now()->subMinutes(5))
                ->select('id', 'name', 'username', 'avatar', 'verified', 'verification_type')
                ->get()
                ->toArray();
        });
    }

    public function getUserStatus(int $userId): array
    {
        $user = User::select('id', 'is_online', 'last_seen_at')
            ->findOrFail($userId);

        return [
            'user_id' => $user->id,
            'is_online' => $user->is_online,
            'last_seen_at' => $user->last_seen_at,
        ];
    }
}
