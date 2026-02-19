<?php

namespace App\Notifications;

use App\Models\{Comment, User};
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification
{
    public function __construct(public Comment $comment, public User $user) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'user_id' => $this->user->id,
            'message' => "{$this->user->name} commented on your post"
        ];
    }
}
