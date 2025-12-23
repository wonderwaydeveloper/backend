<?php

namespace App\Actions\Notification;

use App\Models\Notification;

class MarkAsReadAction
{
    public function execute(Notification $notification): Notification
    {
        $notification->update(['read_at' => now()]);
        return $notification->fresh();
    }

    public function executeAll(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}