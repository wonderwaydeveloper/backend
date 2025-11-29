<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $sender, public $conversation)
    {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Message')
            ->line("You have a new message from {$this->sender->name}.")
            ->action('View Conversation', url('/messages/' . $this->conversation->id))
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_message',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name,
            'sender_username' => $this->sender->username,
            'conversation_id' => $this->conversation->id,
            'message' => "You have a new message from {$this->sender->name}.",
        ];
    }
}