<?php

namespace App\Listeners;

use App\Events\UserFollowed;
use App\Jobs\SendNotificationJob;

class SendFollowNotification
{
    public function handle(UserFollowed $event): void
    {
        SendNotificationJob::dispatch(
            $event->followedUser->id,
            $event->follower->id,
            'follow',
            $event->follower->id,
            get_class($event->follower)
        );
    }
}
