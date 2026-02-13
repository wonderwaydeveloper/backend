<?php

namespace App\Listeners;

use App\Events\UserFollowed;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendFollowNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function handle(UserFollowed $event): void
    {
        $this->notificationService->notifyFollow($event->follower, $event->followedUser);
    }
}
