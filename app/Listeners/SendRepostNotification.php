<?php

namespace App\Listeners;

use App\Events\PostReposted;
use App\Jobs\SendNotificationJob;

class SendRepostNotification
{
    public function handle(PostReposted $event): void
    {
        if ($event->post->user_id === $event->user->id) {
            return;
        }

        SendNotificationJob::dispatch(
            $event->post->user_id,
            $event->user->id,
            $event->isQuote ? 'quote' : 'repost',
            $event->repost->id,
            get_class($event->repost)
        );
    }
}
