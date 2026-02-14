<?php

namespace App\Events;

use App\Models\Poll;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PollVoted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Poll $poll,
        public User $voter
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('poll.' . $this->poll->id),
        ];
    }
}
