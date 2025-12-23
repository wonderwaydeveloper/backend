<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($this->isCurrentUser(), $this->email),
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'is_premium' => $this->is_premium,
            'is_private' => $this->is_private,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at,
            'created_at' => $this->created_at,
            
            // Counts
            'posts_count' => $this->when($this->relationLoaded('posts'), $this->posts_count),
            'followers_count' => $this->when($this->relationLoaded('followers'), $this->followers_count),
            'following_count' => $this->when($this->relationLoaded('following'), $this->following_count),
            
            // Computed fields
            'is_following' => $this->when(
                auth()->check() && !$this->isCurrentUser(),
                fn() => auth()->user()->isFollowing($this->id)
            ),
        ];
    }
    
    private function isCurrentUser(): bool
    {
        return auth()->check() && auth()->id() === $this->id;
    }
}