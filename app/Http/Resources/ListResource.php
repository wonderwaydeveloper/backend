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
            'privacy' => $this->privacy,
            'banner_image' => $this->banner_image,
            'members_count' => $this->members_count ?? 0,
            'subscribers_count' => $this->subscribers_count ?? 0,
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