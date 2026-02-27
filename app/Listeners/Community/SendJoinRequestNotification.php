<?php

namespace App\Listeners\Community;

use App\Events\JoinRequestCreated;
use App\Notifications\JoinRequestNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendJoinRequestNotification implements ShouldQueue
{
    public function handle(JoinRequestCreated $event): void
    {
        $joinRequest = $event->joinRequest;
        
        // Notify community admins
        $joinRequest->community->admins()->each(function ($admin) use ($joinRequest) {
            $admin->notify(new JoinRequestNotification($joinRequest));
        });
    }
}
