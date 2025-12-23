<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class FollowDTO
{
    public function __construct(
        public readonly int $followerId,
        public readonly int $followingId,
        public readonly string $status = 'following',
        public readonly ?string $followedAt = null
    ) {}

    public static function fromRequest(Request $request, int $followerId): self
    {
        return new self(
            followerId: $followerId,
            followingId: $request->following_id ?? $request->user_id,
            status: $request->status ?? 'following',
            followedAt: now()->toDateTimeString()
        );
    }

    public static function create(int $followerId, int $followingId): self
    {
        return new self(
            followerId: $followerId,
            followingId: $followingId,
            followedAt: now()->toDateTimeString()
        );
    }

    public function toArray(): array
    {
        return [
            'follower_id' => $this->followerId,
            'following_id' => $this->followingId,
            'status' => $this->status,
            'followed_at' => $this->followedAt,
        ];
    }
}