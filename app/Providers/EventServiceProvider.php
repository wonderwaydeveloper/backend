<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\CommentDeleted;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\{CommunityCreated, MemberJoined, MemberLeft, JoinRequestCreated, JoinRequestApproved, JoinRequestRejected};
use App\Listeners\SendCommentNotification;
use App\Listeners\SendMessageNotification;
use App\Listeners\Community\{SendCommunityCreatedNotification, SendMemberJoinedNotification, SendMemberLeftNotification, SendJoinRequestNotification, SendJoinRequestApprovedNotification, SendJoinRequestRejectedNotification, UpdateCommunityCounters};
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
        
        // Community Events
        CommunityCreated::class => [
            SendCommunityCreatedNotification::class,
        ],
        MemberJoined::class => [
            SendMemberJoinedNotification::class,
            UpdateCommunityCounters::class . '@handleMemberJoined',
        ],
        MemberLeft::class => [
            SendMemberLeftNotification::class,
            UpdateCommunityCounters::class . '@handleMemberLeft',
        ],
        JoinRequestCreated::class => [
            SendJoinRequestNotification::class,
        ],
        JoinRequestApproved::class => [
            SendJoinRequestApprovedNotification::class,
        ],
        JoinRequestRejected::class => [
            SendJoinRequestRejectedNotification::class,
        ],
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
