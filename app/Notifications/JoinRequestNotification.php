<?php

namespace App\Notifications;

use App\Models\CommunityJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class JoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public CommunityJoinRequest $joinRequest) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'join_request',
            'join_request_id' => $this->joinRequest->id,
            'community_id' => $this->joinRequest->community_id,
            'community_name' => $this->joinRequest->community->name,
            'user_id' => $this->joinRequest->user_id,
            'user_name' => $this->joinRequest->user->name,
            'message' => "{$this->joinRequest->user->name} requested to join {$this->joinRequest->community->name}",
        ];
    }
}
