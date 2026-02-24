<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendMessageNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $conversation = $message->conversation;
        $sender = $message->sender;
        
        $recipientId = $conversation->user_one_id === $sender->id 
            ? $conversation->user_two_id 
            : $conversation->user_one_id;

        $recipient = \App\Models\User::find($recipientId);
        
        if ($recipient) {
            $this->notificationService->sendToUser(
                $recipient,
                'message',
                ['message_id' => $message->id, 'conversation_id' => $conversation->id, 'sender_name' => $sender->name]
            );
        }
    }
}
