<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\MessageRepositoryInterface;
use App\DTOs\MessageDTO;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function find(int $id): ?Message
    {
        return Message::find($id);
    }

    public function create(MessageDTO $dto): Message
    {
        return Message::create($dto->toArray());
    }

    public function update(int $id, MessageDTO $dto): Message
    {
        $message = Message::findOrFail($id);
        $message->update($dto->toArray());
        return $message->fresh();
    }

    public function delete(int $id): bool
    {
        return Message::destroy($id) > 0;
    }

    public function getConversation(int $userId1, int $userId2): LengthAwarePaginator
    {
        return Message::where(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId1)->where('receiver_id', $userId2);
        })->orWhere(function ($query) use ($userId1, $userId2) {
            $query->where('sender_id', $userId2)->where('receiver_id', $userId1);
        })
        ->with(['sender:id,name,username,avatar', 'receiver:id,name,username,avatar'])
        ->orderBy('created_at', 'desc')
        ->paginate(50);
    }

    public function getUserConversations(int $userId): Collection
    {
        return Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender:id,name,username,avatar', 'receiver:id,name,username,avatar'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id === $userId 
                    ? $message->receiver_id 
                    : $message->sender_id;
            })
            ->map(function ($messages) {
                return $messages->first();
            })
            ->values();
    }

    public function markAsRead(int $messageId, int $userId): bool
    {
        return Message::where('id', $messageId)
            ->where('receiver_id', $userId)
            ->update(['is_read' => true]) > 0;
    }

    public function getUnreadCount(int $userId): int
    {
        return Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    public function getUnreadMessages(int $userId): Collection
    {
        return Message::where('receiver_id', $userId)
            ->where('is_read', false)
            ->with(['sender:id,name,username,avatar'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchMessages(int $userId, string $query): LengthAwarePaginator
    {
        return Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
        })
        ->where('content', 'like', "%{$query}%")
        ->with(['sender:id,name,username,avatar', 'receiver:id,name,username,avatar'])
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    }
}