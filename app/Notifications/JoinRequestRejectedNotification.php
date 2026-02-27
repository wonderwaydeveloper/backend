<?php

namespace App\Notifications;

use App\Models\CommunityJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class JoinRequestRejectedNotification extends Notification implements ShouldQueue
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
            'type' => 'join_request_rejected',
            'join_request_id' => $this->joinRequest->id,
            'community_id' => $this->joinRequest->community_id,
            'community_name' => $this->joinRequest->community->name,
            'message' => "Your request to join {$this->joinRequest->community->name} has been rejected",
        ];
    }
}
