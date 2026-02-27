<?php

namespace App\Listeners\Community;

use App\Events\CommunityCreated;
use App\Notifications\CommunityCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCommunityCreatedNotification implements ShouldQueue
{
    public function handle(CommunityCreated $event): void
    {
        $community = $event->community;
        
        // Notify followers of creator
        $community->creator->followers()
            ->chunk(100, function ($followers) use ($community) {
                foreach ($followers as $follower) {
                    $follower->notify(new CommunityCreatedNotification($community));
                }
            });
    }
}
