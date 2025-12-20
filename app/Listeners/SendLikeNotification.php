<?php

namespace App\Listeners;

use App\Events\PostLiked;
use App\Jobs\SendNotificationJob;

class SendLikeNotification
{
    public function handle(PostLiked $event): void
    {
        if ($event->post->user_id === $event->user->id) {
            return;
        }

        SendNotificationJob::dispatch(
            $event->post->user_id,
            $event->user->id,
            'like',
            $event->post->id,
            get_class($event->post)
        );
    }
}
