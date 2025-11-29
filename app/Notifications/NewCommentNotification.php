<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $commenter, public $commentable)
    {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $type = class_basename($this->commentable);
        
        return (new MailMessage)
            ->subject('New Comment')
            ->line("{$this->commenter->name} commented on your {$type}.")
            ->action('View ' . $type, $this->getCommentableUrl())
            ->line('Thank you for using our application!');
    }

    public function toArray($notifiable): array
    {
        $type = class_basename($this->commentable);
        
        return [
            'type' => 'new_comment',
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'commenter_username' => $this->commenter->username,
            'commentable_type' => get_class($this->commentable),
            'commentable_id' => $this->commentable->id,
            'message' => "{$this->commenter->name} commented on your {$type}.",
        ];
    }

    private function getCommentableUrl(): string
    {
        return match (class_basename($this->commentable)) {
            'Post' => url('/posts/' . $this->commentable->id),
            'Article' => url('/articles/' . $this->commentable->slug),
            default => url('/'),
        };
    }
}