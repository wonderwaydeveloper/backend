<?php

namespace App\Events;

use App\Models\{UserList, User};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserList $list,
        public User $user
    ) {}

    public function broadcastOn()
    {
        return new PresenceChannel('user.' . $this->user->id);
    }

    public function broadcastWith()
    {
        return [
            'list' => [
                'id' => $this->list->id,
                'name' => $this->list->name,
                'privacy' => $this->list->privacy,
            ],
        ];
    }
}

class ListMemberAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserList $list,
        public User $user
    ) {}

    public function broadcastOn()
    {
        return new PresenceChannel('list.' . $this->list->id);
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'username', 'avatar']),
            'members_count' => $this->list->members_count,
        ];
    }
}

class ListMemberRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserList $list,
        public User $user
    ) {}

    public function broadcastOn()
    {
        return new PresenceChannel('list.' . $this->list->id);
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'username', 'avatar']),
            'members_count' => $this->list->members_count,
        ];
    }
}

class ListSubscribed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UserList $list,
        public User $user
    ) {}

    public function broadcastOn()
    {
        return new PresenceChannel('list.' . $this->list->id);
    }

    public function broadcastWith()
    {
        return [
            'user' => $this->user->only(['id', 'name', 'username', 'avatar']),
            'subscribers_count' => $this->list->subscribers_count,
        ];
    }
}
