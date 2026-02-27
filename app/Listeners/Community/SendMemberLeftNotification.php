<?php

namespace App\Listeners\Community;

use App\Events\MemberLeft;
use App\Notifications\MemberLeftNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMemberLeftNotification implements ShouldQueue
{
    public function handle(MemberLeft $event): void
    {
        $community = $event->community;
        $user = $event->user;
        
        // Notify community admins
        $community->admins()->each(function ($admin) use ($community, $user) {
            $admin->notify(new MemberLeftNotification($community, $user));
        });
    }
}
