<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Jobs\ProcessMessageJob;
use App\Models\{Conversation, ConversationParticipant, ConversationSetting, Message, MessageEdit, MessageReaction, User};
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
        return Conversation::where(function($query) use ($user) {
            // Direct conversations
            $query->where(function($q) use ($user) {
                $q->where('type', 'direct')
                  ->where(function($q2) use ($user) {
                      $q2->where('user_one_id', $user->id)
                         ->orWhere('user_two_id', $user->id);
                  });
            })
            // Group conversations
            ->orWhere(function($q) use ($user) {
                $q->where('type', 'group')
                  ->whereHas('activeParticipants', function($q2) use ($user) {
                      $q2->where('user_id', $user->id);
                  });
            });
        })
        ->with(['userOne:id,name,username,avatar', 'userTwo:id,name,username,avatar', 'lastMessage', 'activeParticipants:id,name,username,avatar'])
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

    // ==================== GROUP CHAT METHODS ====================

    public function createGroupConversation(User $creator, array $participantIds, string $name): Conversation
    {
        $totalParticipants = count($participantIds) + 1; // +1 for creator
        
        if ($totalParticipants < 3) {
            throw new \Exception('Group must have at least 3 participants');
        }
        
        if ($totalParticipants > 50) {
            throw new \Exception('Group cannot have more than 50 participants');
        }

        return DB::transaction(function () use ($creator, $participantIds, $name) {
            $conversation = Conversation::create([
                'name' => $name,
                'type' => 'group',
                'max_participants' => 50,
                'last_message_at' => now(),
            ]);

            // Add creator as owner
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $creator->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);

            // Add other participants
            foreach ($participantIds as $userId) {
                if ($userId != $creator->id) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'role' => 'member',
                        'joined_at' => now(),
                    ]);
                }
            }

            return $conversation->load('participants');
        });
    }

    public function addParticipant(Conversation $conversation, User $adder, int $newParticipantId): void
    {
        if (!$conversation->isGroup()) {
            throw new \Exception('Cannot add participants to direct conversation');
        }

        $adderParticipant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $adder->id)
            ->whereNull('left_at')
            ->first();

        if (!$adderParticipant || !$adderParticipant->isAdmin()) {
            throw new \Exception('Only admins can add participants');
        }

        if (!$conversation->canAddParticipant()) {
            throw new \Exception('Group is full (max 50 participants)');
        }

        if ($conversation->hasParticipant($newParticipantId)) {
            throw new \Exception('User is already a participant');
        }

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $newParticipantId,
            'role' => 'member',
            'joined_at' => now(),
        ]);
    }

    public function removeParticipant(Conversation $conversation, User $remover, int $participantId): void
    {
        if (!$conversation->isGroup()) {
            throw new \Exception('Cannot remove participants from direct conversation');
        }

        $removerParticipant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $remover->id)
            ->whereNull('left_at')
            ->first();

        if (!$removerParticipant || !$removerParticipant->isAdmin()) {
            throw new \Exception('Only admins can remove participants');
        }

        $targetParticipant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $participantId)
            ->whereNull('left_at')
            ->first();

        if (!$targetParticipant) {
            throw new \Exception('User is not a participant');
        }

        if ($targetParticipant->isOwner()) {
            throw new \Exception('Cannot remove group owner');
        }

        $targetParticipant->update(['left_at' => now()]);
    }

    public function leaveGroup(Conversation $conversation, User $user): void
    {
        if (!$conversation->isGroup()) {
            throw new \Exception('Cannot leave direct conversation');
        }

        $participant = ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            throw new \Exception('You are not a participant');
        }

        if ($participant->isOwner()) {
            throw new \Exception('Owner cannot leave group. Transfer ownership first');
        }

        $participant->update(['left_at' => now()]);
    }

    public function sendGroupMessage(User $sender, Conversation $conversation, array $data): Message
    {
        if (!$conversation->isGroup()) {
            throw new \Exception('Not a group conversation');
        }

        if (!$conversation->hasParticipant($sender->id)) {
            throw new \Exception('You are not a member of this group');
        }

        return DB::transaction(function () use ($sender, $conversation, $data) {
            $messageData = [
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'content' => isset($data['content']) ? htmlspecialchars(strip_tags($data['content']), ENT_QUOTES, 'UTF-8') : null,
            ];

            if (isset($data['gif_url'])) {
                $messageData['gif_url'] = $data['gif_url'];
            }

            $message = Message::create($messageData);
            
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
    }

    // ==================== MESSAGE REACTIONS ====================

    public function addReaction(Message $message, User $user, string $emoji): void
    {
        if (!MessageReaction::isValidEmoji($emoji)) {
            throw new \Exception('Invalid emoji. Allowed: ❤️, 😂, 👍, 😮, 😢, 🙏');
        }

        MessageReaction::updateOrCreate(
            [
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]
        );
    }

    public function removeReaction(Message $message, User $user, string $emoji): void
    {
        MessageReaction::where([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ])->delete();
    }

    public function getReactionsSummary(Message $message): array
    {
        $reactions = $message->reactions()
            ->selectRaw('emoji, count(*) as count')
            ->groupBy('emoji')
            ->get();

        return $reactions->mapWithKeys(function ($reaction) {
            return [$reaction->emoji => $reaction->count];
        })->toArray();
    }

    // ==================== VOICE MESSAGES ====================

    public function sendVoiceMessage(User $sender, User $recipient, $audioFile): Message
    {
        if ($sender->id === $recipient->id) {
            throw new \Exception('Cannot send message to yourself');
        }

        if ($sender->hasBlocked($recipient->id) || $recipient->hasBlocked($sender->id)) {
            throw new \Exception('Cannot send message to blocked user');
        }

        try {
            return DB::transaction(function () use ($sender, $recipient, $audioFile) {
                $conversation = Conversation::between($sender->id, $recipient->id);

                if (!$conversation) {
                    $conversation = Conversation::create([
                        'user_one_id' => $sender->id,
                        'user_two_id' => $recipient->id,
                        'last_message_at' => now(),
                    ]);
                }

                // Upload audio file
                $media = app(\App\Services\MediaService::class)->uploadAudio($audioFile, $sender);
                
                // Get duration using FFmpeg
                $duration = $this->getAudioDuration($media->path);
                
                if ($duration > 300) {
                    $media->delete();
                    throw new \Exception('Voice message too long (max 5 minutes)');
                }

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'message_type' => 'voice',
                    'voice_duration' => $duration,
                ]);

                app(\App\Services\MediaService::class)->attachToModel($media, $message);
                
                $conversation->update(['last_message_at' => now()]);
                $message->load('sender:id,name,username,avatar', 'media');

                broadcast(new MessageSent($message));
                ProcessMessageJob::dispatch($message);

                return $message;
            });
        } catch (\Exception $e) {
            Log::error('Failed to send voice message', [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function sendGroupVoiceMessage(User $sender, Conversation $conversation, $audioFile): Message
    {
        if (!$conversation->isGroup()) {
            throw new \Exception('Not a group conversation');
        }

        if (!$conversation->hasParticipant($sender->id)) {
            throw new \Exception('You are not a member of this group');
        }

        try {
            return DB::transaction(function () use ($sender, $conversation, $audioFile) {
                $media = app(\App\Services\MediaService::class)->uploadAudio($audioFile, $sender);
                
                $duration = $this->getAudioDuration($media->path);
                
                if ($duration > 300) {
                    $media->delete();
                    throw new \Exception('Voice message too long (max 5 minutes)');
                }

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'message_type' => 'voice',
                    'voice_duration' => $duration,
                ]);

                app(\App\Services\MediaService::class)->attachToModel($media, $message);
                
                $conversation->update(['last_message_at' => now()]);
                $message->load('sender:id,name,username,avatar', 'media');

                broadcast(new MessageSent($message));
                ProcessMessageJob::dispatch($message);

                return $message;
            });
        } catch (\Exception $e) {
            Log::error('Failed to send group voice message', [
                'sender_id' => $sender->id,
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function getAudioDuration(string $path): int
    {
        try {
            $ffprobe = \FFMpeg\FFProbe::create();
            return (int) $ffprobe->format($path)->get('duration');
        } catch (\Exception $e) {
            Log::warning('Could not get audio duration', ['path' => $path, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    // ==================== MESSAGE SEARCH ====================

    public function searchMessages(User $user, string $query, ?int $conversationId = null): array
    {
        if (empty($query)) {
            return [];
        }

        // Get user's conversation IDs
        $conversationIds = $this->getUserConversationIds($user);

        if (empty($conversationIds)) {
            return [];
        }

        // Build search query
        $searchQuery = Message::search($query)
            ->where('message_type', 'text');

        // Filter by specific conversation if provided
        if ($conversationId) {
            if (!in_array($conversationId, $conversationIds)) {
                throw new \Exception('You do not have access to this conversation');
            }
            $searchQuery->where('conversation_id', $conversationId);
        } else {
            // Filter by user's conversations
            $searchQuery->whereIn('conversation_id', $conversationIds);
        }

        $results = $searchQuery
            ->take(50)
            ->get();

        return $results->load('sender:id,name,username,avatar', 'conversation')->toArray();
    }

    private function getUserConversationIds(User $user): array
    {
        // Get direct conversations
        $directConversations = Conversation::where('type', 'direct')
            ->where(function($q) use ($user) {
                $q->where('user_one_id', $user->id)
                  ->orWhere('user_two_id', $user->id);
            })
            ->pluck('id')
            ->toArray();

        // Get group conversations
        $groupConversations = Conversation::where('type', 'group')
            ->whereHas('activeParticipants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->pluck('id')
            ->toArray();

        return array_merge($directConversations, $groupConversations);
    }

    // ==================== FORWARD/EDIT/DELETE ====================

    public function forwardMessage(Message $message, User $sender, array $recipientIds): array
    {
        if (count($recipientIds) > 10) {
            throw new \Exception('Cannot forward to more than 10 recipients');
        }

        $forwardedMessages = [];

        foreach ($recipientIds as $recipientId) {
            $recipient = User::find($recipientId);
            
            if (!$recipient || $sender->id === $recipientId) {
                continue;
            }

            if ($sender->hasBlocked($recipientId) || $recipient->hasBlocked($sender->id)) {
                continue;
            }

            $conversation = Conversation::between($sender->id, $recipientId);

            if (!$conversation) {
                $conversation = Conversation::create([
                    'user_one_id' => $sender->id,
                    'user_two_id' => $recipientId,
                    'last_message_at' => now(),
                ]);
            }

            $forwardedMessage = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message_type' => $message->message_type,
                'content' => $message->content,
                'forwarded_from_message_id' => $message->id,
            ]);

            $conversation->update(['last_message_at' => now()]);
            $forwardedMessages[] = $forwardedMessage;
        }

        return $forwardedMessages;
    }

    public function editMessage(Message $message, User $user, string $newContent): void
    {
        if ($message->sender_id !== $user->id) {
            throw new \Exception('Cannot edit others messages');
        }

        if (!$message->canEdit()) {
            throw new \Exception('Edit time expired (15 minutes)');
        }

        if ($message->message_type !== 'text') {
            throw new \Exception('Can only edit text messages');
        }

        MessageEdit::create([
            'message_id' => $message->id,
            'old_content' => $message->content,
            'new_content' => $newContent,
            'edited_at' => now(),
        ]);

        $message->update([
            'content' => htmlspecialchars(strip_tags($newContent), ENT_QUOTES, 'UTF-8'),
            'edited_at' => now(),
        ]);
    }

    public function deleteForEveryone(Message $message, User $user): void
    {
        if ($message->sender_id !== $user->id) {
            throw new \Exception('Cannot delete others messages');
        }

        if (!$message->canDelete()) {
            throw new \Exception('Delete time expired (48 hours)');
        }

        $message->delete();
    }

    // ==================== CONVERSATION SETTINGS ====================

    public function muteConversation(Conversation $conversation, User $user, ?int $hours = null): void
    {
        $data = [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'is_muted' => true,
        ];

        if ($hours) {
            $data['muted_until'] = now()->addHours($hours);
        }

        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            $data
        );
    }

    public function unmuteConversation(Conversation $conversation, User $user): void
    {
        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['is_muted' => false, 'muted_until' => null]
        );
    }

    public function archiveConversation(Conversation $conversation, User $user): void
    {
        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['is_archived' => true]
        );
    }

    public function unarchiveConversation(Conversation $conversation, User $user): void
    {
        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['is_archived' => false]
        );
    }

    public function pinConversation(Conversation $conversation, User $user): void
    {
        // Max 3 pinned conversations
        $pinnedCount = ConversationSetting::where('user_id', $user->id)
            ->where('is_pinned', true)
            ->count();

        if ($pinnedCount >= 3) {
            throw new \Exception('Maximum 3 pinned conversations allowed');
        }

        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['is_pinned' => true]
        );
    }

    public function unpinConversation(Conversation $conversation, User $user): void
    {
        ConversationSetting::updateOrCreate(
            ['conversation_id' => $conversation->id, 'user_id' => $user->id],
            ['is_pinned' => false]
        );
    }
}
