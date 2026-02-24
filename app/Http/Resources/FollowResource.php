<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'follower_id' => $this->follower_id,
            'following_id' => $this->following_id,
            'created_at' => $this->created_at,
            'follower' => new UserResource($this->whenLoaded('follower')),
            'following' => new UserResource($this->whenLoaded('following')),
        ];
    }
}
