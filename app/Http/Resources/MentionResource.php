<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MentionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
            ],
            'mentionable_type' => class_basename($this->mentionable_type),
            'mentionable_id' => $this->mentionable_id,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
