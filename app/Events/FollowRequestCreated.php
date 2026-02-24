<?php

namespace App\Events;

use App\Models\FollowRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FollowRequestCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public FollowRequest $followRequest
    ) {
    }
}
