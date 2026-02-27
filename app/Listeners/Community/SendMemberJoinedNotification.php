<?php

namespace App\Listeners\Community;

use App\Events\MemberJoined;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMemberJoinedNotification implements ShouldQueue
{
    public function handle(MemberJoined $event): void
    {
        $community = $event->community;
        $user = $event->user;
        
        // Notify community admins
        $community->admins()->each(function ($admin) use ($community, $user) {
            $admin->notify(new MemberJoinedNotification($community, $user));
        });
    }
}
