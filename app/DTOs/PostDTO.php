<?php

namespace App\DTOs;

class PostDTO
{
    public function __construct(
        public readonly string $content,
        public readonly int $userId,
        public readonly ?array $mediaIds = null,
        public readonly ?string $gifUrl = null,
        public readonly string $replySettings = 'everyone',
        public readonly bool $isDraft = false,
        public readonly ?int $quotedPostId = null,
        public readonly ?int $threadId = null,
        public readonly ?int $threadPosition = null
    ) {}

    public static function fromRequest(array $data, int $userId): self
    {
        return new self(
            content: $data['content'],
            userId: $userId,
            mediaIds: $data['media_ids'] ?? null,
            gifUrl: $data['gif_url'] ?? null,
            replySettings: $data['reply_settings'] ?? 'everyone',
            isDraft: $data['is_draft'] ?? false,
            quotedPostId: $data['quoted_post_id'] ?? null,
            threadId: $data['thread_id'] ?? null,
            threadPosition: $data['thread_position'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'user_id' => $this->userId,
            'gif_url' => $this->gifUrl,
            'reply_settings' => $this->replySettings,
            'is_draft' => $this->isDraft,
            'quoted_post_id' => $this->quotedPostId,
            'thread_id' => $this->threadId,
            'thread_position' => $this->threadPosition,
            'published_at' => $this->isDraft ? null : now(),
        ];
    }
}