<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $requester)
    {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Follow Request')
            ->line("{$this->requester->name} requested to follow you.")
            ->action('Manage Requests', url('/users/me/follow-requests'))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'follow_request',
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_username' => $this->requester->username,
            'message' => "{$this->requester->name} requested to follow you.",
        ];
    }
}