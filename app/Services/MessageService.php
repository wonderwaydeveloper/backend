<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Jobs\ProcessMessageJob;
use App\Models\{Conversation, Message, User};
use Illuminate\Support\Facades\{DB, Log};

class MessageService
{
    public function sendMessage(User $sender, User $recipient, array $data): Message
    {
        if ($sender->id === $recipient->id) {
            throw new \Exception('Cannot send message to yourself');
        }

        if ($sender->hasBlocked($recipient->id) || $recipient->hasBlocked($sender->id)) {
            throw new \Exception('Cannot send message to blocked user');
        }

        if ($sender->hasMuted($recipient->id)) {
            throw new \Exception('Cannot send message to muted user');
        }

        // Check DM settings
        $dmSettings = $recipient->notification_preferences['dm_settings'] ?? 'everyone';
        if ($dmSettings === 'none') {
            throw new \Exception('User does not accept direct messages');
        }
        if ($dmSettings === 'followers') {
            if (!method_exists($recipient, 'isFollowing') || !$recipient->isFollowing($sender->id)) {
                throw new \Exception('User only accepts messages from followers');
            }
        }

        try {
            return DB::transaction(function () use ($sender, $recipient, $data) {
                $conversation = Conversation::between($sender->id, $recipient->id);

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'user_one_id' => $sender->id,
                        'user_two_id' => $recipient->id,
                        'last_message_at' => now(),
                    ]);
                }

                $messageData = [
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'content' => isset($data['content']) ? htmlspecialchars(strip_tags($data['content']), ENT_QUOTES, 'UTF-8') : null,
                ];

                if (isset($data['gif_url'])) {
                    $messageData['gif_url'] = $data['gif_url'];
                }

                $message = Message::create($messageData);
                
                // Handle media attachments
                if (isset($data['attachments'])) {
                    foreach ($data['attachments'] as $file) {
                        $media = app(\App\Services\MediaService::class)->uploadDocument($file, $sender);
                        app(\App\Services\MediaService::class)->attachToModel($media, $message);
                    }
                }
                
                $conversation->update(['last_message_at' => now()]);
                $message->load('sender:id,name,username,avatar', 'media');

                broadcast(new MessageSent($message));
                ProcessMessageJob::dispatch($message);

                return $message;
            });
        } catch (\Exception $e) {
            Log::error('Failed to send message', [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getConversations(User $user, int $perPage = 20)
    {
        return Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->with(['userOne:id,name,username,avatar', 'userTwo:id,name,username,avatar', 'lastMessage'])
            ->orderBy('last_message_at', 'desc')
            ->paginate($perPage);
    }

    public function getMessages(User $currentUser, User $otherUser, int $perPage = 50)
    {
        $conversation = Conversation::between($currentUser->id, $otherUser->id);

        if (!$conversation) {
            return null;
        }

        $messages = $conversation->messages()
            ->with('sender:id,name,username,avatar')
            ->latest()
            ->paginate($perPage);

        $this->markConversationAsRead($conversation, $currentUser->id, $otherUser->id);

        return $messages;
    }

    public function markAsRead(Message $message, User $user): void
    {
        if ($message->sender_id === $user->id) {
            throw new \Exception('Cannot mark own message as read');
        }

        $message->markAsRead();
    }

    public function getUnreadCount(User $user): int
    {
        return Message::join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->where(function($query) use ($user) {
                $query->where('conversations.user_one_id', $user->id)
                      ->orWhere('conversations.user_two_id', $user->id);
            })
            ->where('messages.sender_id', '!=', $user->id)
            ->whereNull('messages.read_at')
            ->whereNull('messages.deleted_at')
            ->count();
    }

    private function markConversationAsRead(Conversation $conversation, int $currentUserId, int $otherUserId): void
    {
        $conversation->messages()
            ->where('sender_id', $otherUserId)
            ->unread()
            ->update(['read_at' => now()]);
    }
}
