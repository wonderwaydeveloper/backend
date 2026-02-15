<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnlineUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'verified' => $this->verified,
            'verification_type' => $this->verification_type,
            'is_online' => $this->is_online,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
        ];
    }
}
