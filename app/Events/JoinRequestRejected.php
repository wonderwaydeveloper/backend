<?php

namespace App\Events;

use App\Models\CommunityJoinRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JoinRequestRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public CommunityJoinRequest $joinRequest
    ) {
    }
}
