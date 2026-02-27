<?php

namespace App\Listeners\Community;

use App\Events\JoinRequestApproved;
use App\Notifications\JoinRequestApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendJoinRequestApprovedNotification implements ShouldQueue
{
    public function handle(JoinRequestApproved $event): void
    {
        $joinRequest = $event->joinRequest;
        
        // Notify user who requested to join
        $joinRequest->user->notify(new JoinRequestApprovedNotification($joinRequest));
    }
}
