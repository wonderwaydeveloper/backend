<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateInactiveUsersStatus extends Command
{
    protected $signature = 'realtime:update-inactive-users';
    protected $description = 'Mark users as offline if inactive for more than 5 minutes';

    public function handle()
    {
        $count = User::where('is_online', true)
            ->where('last_seen_at', '<', now()->subMinutes(5))
            ->update([
                'is_online' => false,
            ]);

        $this->info("Updated {$count} inactive users to offline status.");
    }
}
