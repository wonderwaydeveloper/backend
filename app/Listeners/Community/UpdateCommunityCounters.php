<?php

namespace App\Listeners\Community;

use App\Events\{MemberJoined, MemberLeft};

class UpdateCommunityCounters
{
    public function handleMemberJoined(MemberJoined $event): void
    {
        $event->community->increment('member_count');
    }

    public function handleMemberLeft(MemberLeft $event): void
    {
        $event->community->decrement('member_count');
    }
}
