<?php

namespace App\Http\Controllers\Api;

use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\{CreateGroupRequest, SendMessageRequest};
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\{Conversation, Message, User};
use App\Services\MessageService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MessageController extends Controller
{
    public function __construct(
        private MessageService $messageService
    ) {}
    public function conversations(Request $request)
    {
        $conversations = $this->messageService->getConversations($request->user());

        return ConversationResource::collection($conversations);
    }

    public function messages(Request $request, User $user)
    {
        $messages = $this->messageService->getMessages($request->user(), $user);

        if (!$messages) {
            return response()->json(['data' => [], 'current_page' => 1, 'per_page' => 50]);
        }

        return MessageResource::collection($messages);
    }

    public function send(SendMessageRequest $request, User $user)
    {
        try {
            $validated = $request->validated();
            $data = ['content' => $validated['content'] ?? null];

            if (isset($validated['gif_url'])) {
                $data['gif_url'] = $validated['gif_url'];
            }
            
            if ($request->hasFile('attachments')) {
                $data['attachments'] = $request->file('attachments');
            }

            $message = $this->messageService->sendMessage($request->user(), $user, $data);

            return new MessageResource($message);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function typing(Request $request, User $user)
    {
        $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $currentUser = $request->user();
        $conversation = Conversation::between($currentUser->id, $user->id);

        if ($conversation) {
            broadcast(new UserTyping(
                $conversation->id,
                $currentUser->id,
                $currentUser->name,
                $request->is_typing
            ));
        }

        return response()->json(['status' => 'sent']);
    }

    public function markAsRead(Request $request, Message $message)
    {
        try {
            if ($message->sender_id === $request->user()->id) {
                return response()->json(['message' => 'Cannot mark own message as read'], Response::HTTP_BAD_REQUEST);
            }
            
            $this->messageService->markAsRead($message, $request->user());
            return response()->json(['message' => 'Marked as read']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function unreadCount(Request $request)
    {
        $count = $this->messageService->getUnreadCount($request->user());
        return response()->json(['count' => $count]);
    }

    // ==================== GROUP CHAT ENDPOINTS ====================

    public function createGroup(CreateGroupRequest $request)
    {
        try {
            $conversation = $this->messageService->createGroupConversation(
                $request->user(),
                $request->participant_ids,
                $request->name
            );

            return new ConversationResource($conversation);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function sendGroupMessage(SendMessageRequest $request, Conversation $conversation)
    {
        try {
            $validated = $request->validated();
            $data = ['content' => $validated['content'] ?? null];

            if (isset($validated['gif_url'])) {
                $data['gif_url'] = $validated['gif_url'];
            }
            
            if ($request->hasFile('attachments')) {
                $data['attachments'] = $request->file('attachments');
            }

            $message = $this->messageService->sendGroupMessage($request->user(), $conversation, $data);

            return new MessageResource($message);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function addMember(Request $request, Conversation $conversation, User $user)
    {
        try {
            $this->messageService->addParticipant($conversation, $request->user(), $user->id);
            return response()->json(['message' => 'Member added successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeMember(Request $request, Conversation $conversation, User $user)
    {
        try {
            $this->messageService->removeParticipant($conversation, $request->user(), $user->id);
            return response()->json(['message' => 'Member removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function leaveGroup(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->leaveGroup($conversation, $request->user());
            return response()->json(['message' => 'Left group successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getGroupMessages(Request $request, Conversation $conversation)
    {
        try {
            if (!$conversation->isGroup()) {
                return response()->json(['message' => 'Not a group conversation'], Response::HTTP_BAD_REQUEST);
            }

            if (!$conversation->hasParticipant($request->user()->id)) {
                return response()->json(['message' => 'You are not a member'], Response::HTTP_FORBIDDEN);
            }

            $messages = $conversation->messages()
                ->with('sender:id,name,username,avatar', 'media')
                ->latest()
                ->paginate(50);

            return MessageResource::collection($messages);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==================== MESSAGE REACTIONS ====================

    public function addReaction(Request $request, Message $message)
    {
        $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        try {
            $this->messageService->addReaction($message, $request->user(), $request->emoji);
            return response()->json(['message' => 'Reaction added successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function removeReaction(Request $request, Message $message, string $emoji)
    {
        try {
            $this->messageService->removeReaction($message, $request->user(), $emoji);
            return response()->json(['message' => 'Reaction removed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getReactions(Request $request, Message $message)
    {
        try {
            $summary = $this->messageService->getReactionsSummary($message);
            return response()->json(['reactions' => $summary]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==================== VOICE MESSAGES ====================

    public function sendVoice(Request $request, User $user)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,ogg,webm,m4a|max:10240',
        ]);

        try {
            $message = $this->messageService->sendVoiceMessage(
                $request->user(),
                $user,
                $request->file('audio')
            );

            return new MessageResource($message);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function sendGroupVoice(Request $request, Conversation $conversation)
    {
        $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,ogg,webm,m4a|max:10240',
        ]);

        try {
            $message = $this->messageService->sendGroupVoiceMessage(
                $request->user(),
                $conversation,
                $request->file('audio')
            );

            return new MessageResource($message);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==================== MESSAGE SEARCH ====================

    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
        ]);

        try {
            $results = $this->messageService->searchMessages(
                $request->user(),
                $request->input('query'),
                $request->input('conversation_id')
            );

            return response()->json([
                'results' => $results,
                'count' => count($results),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==================== FORWARD/EDIT/DELETE ====================

    public function forward(Request $request, Message $message)
    {
        $request->validate([
            'recipient_ids' => 'required|array|min:1|max:10',
            'recipient_ids.*' => 'required|integer|exists:users,id',
        ]);

        try {
            $forwardedMessages = $this->messageService->forwardMessage(
                $message,
                $request->user(),
                $request->recipient_ids
            );

            return response()->json([
                'message' => 'Message forwarded successfully',
                'count' => count($forwardedMessages),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function edit(Request $request, Message $message)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        try {
            $this->messageService->editMessage($message, $request->user(), $request->content);
            return response()->json(['message' => 'Message edited successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function deleteForEveryone(Request $request, Message $message)
    {
        try {
            $this->messageService->deleteForEveryone($message, $request->user());
            return response()->json(['message' => 'Message deleted for everyone']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    // ==================== CONVERSATION SETTINGS ====================

    public function muteConversation(Request $request, Conversation $conversation)
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:8760',
        ]);

        try {
            $this->messageService->muteConversation($conversation, $request->user(), $request->hours);
            return response()->json(['message' => 'Conversation muted']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function unmuteConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->unmuteConversation($conversation, $request->user());
            return response()->json(['message' => 'Conversation unmuted']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function archiveConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->archiveConversation($conversation, $request->user());
            return response()->json(['message' => 'Conversation archived']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function unarchiveConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->unarchiveConversation($conversation, $request->user());
            return response()->json(['message' => 'Conversation unarchived']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function pinConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->pinConversation($conversation, $request->user());
            return response()->json(['message' => 'Conversation pinned']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    public function unpinConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->messageService->unpinConversation($conversation, $request->user());
            return response()->json(['message' => 'Conversation unpinned']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
