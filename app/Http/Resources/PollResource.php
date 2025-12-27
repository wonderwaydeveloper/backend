<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PollResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'multiple_choice' => $this->multiple_choice,
            'total_votes' => $this->total_votes ?? 0,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->expires_at < now(),
            'created_at' => $this->created_at,
            'options' => PollOptionResource::collection($this->whenLoaded('options')),
            'user_vote' => $this->when(auth()->check(), function() {
                return $this->votes()->where('user_id', auth()->id())->first();
            })
        ];
    }
}