<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class HashtagDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $postsCount = 0,
        public readonly bool $isTrending = false,
        public readonly ?string $category = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            description: $request->description,
            postsCount: $request->posts_count ?? 0,
            isTrending: $request->boolean('is_trending', false),
            category: $request->category
        );
    }

    public static function fromName(string $name): self
    {
        return new self(
            name: ltrim($name, '#'),
            postsCount: 1
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'posts_count' => $this->postsCount,
            'is_trending' => $this->isTrending,
            'category' => $this->category,
        ];
    }
}