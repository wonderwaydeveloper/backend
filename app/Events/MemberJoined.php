<?php

namespace App\Events;

use App\Models\Community;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberJoined
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Community $community,
        public User $user
    ) {
    }
}
