<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class MessageDTO
{
    public function __construct(
        public readonly int $senderId,
        public readonly int $receiverId,
        public readonly ?string $content = null,
        public readonly ?string $image = null,
        public readonly ?string $video = null,
        public readonly ?string $audio = null,
        public readonly ?int $conversationId = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            senderId: auth()->id(),
            receiverId: $request->receiver_id,
            content: $request->content,
            image: $request->image,
            video: $request->video,
            audio: $request->audio,
            conversationId: $request->conversation_id
        );
    }

    public function toArray(): array
    {
        return [
            'sender_id' => $this->senderId,
            'receiver_id' => $this->receiverId,
            'content' => $this->content,
            'image' => $this->image,
            'video' => $this->video,
            'audio' => $this->audio,
            'conversation_id' => $this->conversationId,
        ];
    }
}