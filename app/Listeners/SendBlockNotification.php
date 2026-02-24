<?php

namespace App\Listeners;

use App\Events\UserBlocked;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendBlockNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private NotificationService $notificationService
    ) {
    }

    public function handle(UserBlocked $event): void
    {
        $this->notificationService->notifyBlock($event->blocker, $event->blocked);
    }
}
