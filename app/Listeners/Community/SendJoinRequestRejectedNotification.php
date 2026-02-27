<?php

namespace App\Listeners\Community;

use App\Events\JoinRequestRejected;
use App\Notifications\JoinRequestRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendJoinRequestRejectedNotification implements ShouldQueue
{
    public function handle(JoinRequestRejected $event): void
    {
        $joinRequest = $event->joinRequest;
        
        // Notify user who requested to join
        $joinRequest->user->notify(new JoinRequestRejectedNotification($joinRequest));
    }
}
