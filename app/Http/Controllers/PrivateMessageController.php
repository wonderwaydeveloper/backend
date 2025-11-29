<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Http\Resources\PrivateMessageResource;
use App\Models\Conversation;
use App\Services\PrivateMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrivateMessageController extends Controller
{
    public function __construct(private PrivateMessageService $privateMessageService) {}

    /**
     * دریافت مکالمات کاربر
     */
    public function conversations(Request $request)
    {
        try {
            $conversations = $this->privateMessageService->getUserConversations($request->user());

            return GenericResource::success(
                $conversations,
                'Conversations retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * ایجاد مکالمه جدید
     */
    public function createConversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'type' => 'sometimes|in:direct,group',
            'title' => 'required_if:type,group|string|max:255',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $conversation = $this->privateMessageService->createConversation(
                $request->user(),
                $request->all()
            );

            return GenericResource::success(
                $conversation,
                'Conversation created successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * ارسال پیام
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media|string|max:5000',
            'type' => 'sometimes|in:text,image,video,file',
            'reply_to' => 'sometimes|exists:private_messages,id',
            'media' => 'sometimes|file|max:10240',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('sendMessage', $conversation);

            $message = $this->privateMessageService->sendMessage(
                $request->user(),
                $conversation,
                $request->all()
            );

            return GenericResource::success(
                new PrivateMessageResource($message->load('user', 'media')),
                'Message sent successfully',
                201
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت پیام‌های مکالمه
     */
    public function messages(Request $request, Conversation $conversation)
    {
        try {
            $this->authorize('view', $conversation);

            $messages = $this->privateMessageService->getConversationMessages(
                $conversation,
                $request->all()
            );

            return GenericResource::success(
                PrivateMessageResource::collection($messages),
                'Messages retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * علامت گذاری پیام‌ها به عنوان دیده شده
     */
    public function markAsSeen(Request $request, Conversation $conversation)
    {
        try {
            $this->authorize('view', $conversation);

            $marked = $this->privateMessageService->markMessagesAsSeen(
                $request->user(),
                $conversation
            );

            return GenericResource::success([
                'marked' => $marked,
            ], 'Messages marked as seen');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * حذف پیام
     */
    public function deleteMessage(Request $request, $messageId)
    {
        try {
            $deleted = $this->privateMessageService->deleteMessage(
                $request->user(),
                $messageId
            );

            return GenericResource::success([
                'deleted' => $deleted,
            ], 'Message deleted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * اضافه کردن کاربر به مکالمه گروهی
     */
    public function addParticipant(Request $request, Conversation $conversation)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('addParticipant', $conversation);

            $added = $this->privateMessageService->addParticipant(
                $conversation,
                $request->user_id
            );

            return GenericResource::success([
                'added' => $added,
            ], 'User added to conversation');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * ترک مکالمه
     */
    public function leaveConversation(Request $request, Conversation $conversation)
    {
        try {
            $this->authorize('view', $conversation);

            $left = $this->privateMessageService->leaveConversation(
                $request->user(),
                $conversation
            );

            return GenericResource::success([
                'left' => $left,
            ], 'Left conversation successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }
}