<?php

namespace App\Listeners;

use App\Events\PollVoted;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPollNotification implements ShouldQueue
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(PollVoted $event): void
    {
        $pollOwner = $event->poll->post->user;
        
        if ($pollOwner->id !== $event->voter->id) {
            $this->notificationService->notifyPollVoted(
                $pollOwner,
                $event->voter,
                $event->poll
            );
        }
    }
}
