<?php

namespace App\Notifications;

use App\Models\{Community, User};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MemberLeftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Community $community,
        public User $user
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'member_left',
            'community_id' => $this->community->id,
            'community_name' => $this->community->name,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'message' => "{$this->user->name} left {$this->community->name}",
        ];
    }
}
