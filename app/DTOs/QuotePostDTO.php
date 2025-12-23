<?php

namespace App\DTOs;

class QuotePostDTO
{
    public function __construct(
        public readonly string $content,
        public readonly int $userId,
        public readonly int $quotedPostId
    ) {}

    public static function fromRequest(array $data, int $userId, int $quotedPostId): self
    {
        return new self(
            content: $data['content'] ?? '',
            userId: $userId,
            quotedPostId: $quotedPostId
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'user_id' => $this->userId,
            'quoted_post_id' => $this->quotedPostId,
            'is_draft' => false,
            'published_at' => now(),
        ];
    }
}