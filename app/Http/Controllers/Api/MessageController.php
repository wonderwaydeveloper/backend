<?php

namespace App\Http\Controllers\Api;

use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\{Conversation, Message, User};
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }
    public function conversations(Request $request)
    {
        $conversations = $this->messageService->getConversations($request->user());

        return response()->json([
            'data' => ConversationResource::collection($conversations)
        ]);
    }

    public function messages(Request $request, User $user)
    {
        $messages = $this->messageService->getMessages($request->user(), $user);

        if (!$messages) {
            return response()->json(['messages' => []]);
        }

        return MessageResource::collection($messages);
    }

    public function send(SendMessageRequest $request, User $user)
    {
        try {
            $validated = $request->validated();
            $data = ['content' => $validated['content'] ?? null];

            if ($request->hasFile('media')) {
                $file = $request->file('media');
                $extension = $file->getClientOriginalExtension();
                $data['media_path'] = $file->store('messages', 'public');
                $data['media_type'] = in_array($extension, ['mp4', 'mov']) ? 'video' : 'image';
            }

            if (isset($validated['gif_url'])) {
                $data['gif_url'] = $validated['gif_url'];
            }

            $message = $this->messageService->sendMessage($request->user(), $user, $data);

            return new MessageResource($message);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
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
            $this->messageService->markAsRead($message, $request->user());
            return response()->json(['message' => 'Marked as read']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function unreadCount(Request $request)
    {
        $count = $this->messageService->getUnreadCount($request->user());
        return response()->json(['count' => $count]);
    }
}
