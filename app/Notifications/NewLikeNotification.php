<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLikeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $liker, public $likeable)
    {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $type = class_basename($this->likeable);
        
        return (new MailMessage)
            ->subject('New Like')
            ->line("{$this->liker->name} liked your {$type}.")
            ->action('View ' . $type, $this->getLikeableUrl())
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        $type = class_basename($this->likeable);
        
        return [
            'type' => 'new_like',
            'liker_id' => $this->liker->id,
            'liker_name' => $this->liker->name,
            'liker_username' => $this->liker->username,
            'likeable_type' => get_class($this->likeable),
            'likeable_id' => $this->likeable->id,
            'message' => "{$this->liker->name} liked your {$type}.",
        ];
    }

    private function getLikeableUrl(): string
    {
        return match (class_basename($this->likeable)) {
            'Post' => url('/posts/' . $this->likeable->id),
            default => url('/'),
        };
    }
}