<?php

namespace App\Contracts\Repositories;

use App\DTOs\MessageDTO;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    public function find(int $id): ?Message;
    
    public function create(MessageDTO $dto): Message;
    
    public function update(int $id, MessageDTO $dto): Message;
    
    public function delete(int $id): bool;
    
    public function getConversation(int $userId1, int $userId2): LengthAwarePaginator;
    
    public function getUserConversations(int $userId): Collection;
    
    public function markAsRead(int $messageId, int $userId): bool;
    
    public function getUnreadCount(int $userId): int;
    
    public function getUnreadMessages(int $userId): Collection;
    
    public function searchMessages(int $userId, string $query): LengthAwarePaginator;
}