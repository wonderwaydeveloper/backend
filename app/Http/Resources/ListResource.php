<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'privacy' => $this->privacy ?? ($this->is_private ? 'private' : 'public'),
            'is_private' => $this->is_private,
            'banner_image' => $this->banner_image,
            'member_count' => $this->member_count ?? 0,
            'subscriber_count' => $this->subscriber_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'is_subscribed' => $this->when(auth()->check(), function() {
                return $this->subscribers()->where('user_id', auth()->id())->exists();
            })
        ];
    }
}