<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendMessageNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CommentCreated::class => [
            SendCommentNotification::class,
        ],
        MessageSent::class => [
            SendMessageNotification::class,
        ],
        UserTyping::class => [],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
