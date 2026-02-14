<?php

namespace App\Listeners;

use App\Events\{SpaceParticipantJoined, SpaceEnded};
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendSpaceNotification implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle($event): void
    {
        if ($event instanceof SpaceParticipantJoined) {
            $this->handleParticipantJoined($event);
        } elseif ($event instanceof SpaceEnded) {
            $this->handleSpaceEnded($event);
        }
    }

    private function handleParticipantJoined(SpaceParticipantJoined $event): void
    {
        if ($event->user->id === $event->space->host_id) {
            return;
        }

        $this->notificationService->notifySpaceJoin(
            $event->space->host,
            $event->user,
            $event->space
        );
    }

    private function handleSpaceEnded(SpaceEnded $event): void
    {
        $participants = $event->space->activeParticipants()->with('user')->get();

        foreach ($participants as $participant) {
            if ($participant->user_id !== $event->space->host_id) {
                $this->notificationService->notifySpaceEnded(
                    $participant->user,
                    $event->space
                );
            }
        }
    }
}
