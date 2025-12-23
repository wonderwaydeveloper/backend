<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class CommentDTO
{
    public function __construct(
        public readonly string $content,
        public readonly int $postId,
        public readonly int $userId,
        public readonly ?int $parentId = null,
        public readonly ?string $image = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            content: $request->content,
            postId: $request->post_id,
            userId: auth()->id(),
            parentId: $request->parent_id,
            image: $request->image
        );
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'parent_id' => $this->parentId,
            'image' => $this->image,
        ];
    }
}