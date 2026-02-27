<?php

namespace App\Notifications;

use App\Models\Community;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CommunityCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Community $community) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'community_created',
            'community_id' => $this->community->id,
            'community_name' => $this->community->name,
            'community_slug' => $this->community->slug,
            'creator_id' => $this->community->created_by,
            'creator_name' => $this->community->creator->name,
            'message' => "{$this->community->creator->name} created a new community: {$this->community->name}",
        ];
    }
}
