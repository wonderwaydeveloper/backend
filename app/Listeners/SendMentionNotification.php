<?php

namespace App\Listeners;

use App\Events\UserMentioned;
use App\Notifications\MentionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMentionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(UserMentioned $event): void
    {
        $event->mentionedUser->notify(
            new MentionNotification($event->mentioner, $event->mentionable)
        );
    }
}
