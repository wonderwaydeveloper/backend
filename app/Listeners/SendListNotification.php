<?php

namespace App\Listeners;

use App\Events\{ListCreated, ListMemberAdded, ListSubscribed};
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendListNotification implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle($event): void
    {
        if ($event instanceof ListMemberAdded) {
            $this->handleMemberAdded($event);
        } elseif ($event instanceof ListSubscribed) {
            $this->handleSubscribed($event);
        }
    }

    private function handleMemberAdded(ListMemberAdded $event): void
    {
        if ($event->user->id === $event->list->user_id) {
            return;
        }

        $this->notificationService->notifyListMemberAdded(
            $event->user,
            $event->list
        );
    }

    private function handleSubscribed(ListSubscribed $event): void
    {
        if ($event->user->id === $event->list->user_id) {
            return;
        }

        $this->notificationService->notifyListSubscribed(
            $event->list->owner,
            $event->user,
            $event->list
        );
    }
}
