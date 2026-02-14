<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\{Channel, InteractsWithSockets, PresenceChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $mentionedUser,
        public User $mentioner,
        public $mentionable
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('user.' . $this->mentionedUser->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'mentioner' => [
                'id' => $this->mentioner->id,
                'username' => $this->mentioner->username,
                'name' => $this->mentioner->name,
                'avatar' => $this->mentioner->avatar,
            ],
            'type' => class_basename($this->mentionable),
            'id' => $this->mentionable->id,
        ];
    }
}
