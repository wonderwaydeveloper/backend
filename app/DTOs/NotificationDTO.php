<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class NotificationDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly array $data,
        public readonly ?int $senderId = null,
        public readonly ?string $title = null,
        public readonly ?string $message = null,
        public readonly ?string $actionUrl = null,
        public readonly bool $isRead = false
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->user_id,
            type: $request->type,
            data: $request->data ?? [],
            senderId: $request->sender_id,
            title: $request->title,
            message: $request->message,
            actionUrl: $request->action_url,
            isRead: $request->boolean('is_read', false)
        );
    }

    public static function create(int $userId, string $type, array $data, ?int $senderId = null): self
    {
        return new self(
            userId: $userId,
            type: $type,
            data: $data,
            senderId: $senderId
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'type' => $this->type,
            'data' => $this->data,
            'sender_id' => $this->senderId,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'is_read' => $this->isRead,
        ];
    }
}