<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_one_id' => $this->user_one_id,
            'user_two_id' => $this->user_two_id,
            'user_one' => new UserResource($this->whenLoaded('userOne')),
            'user_two' => new UserResource($this->whenLoaded('userTwo')),
            'last_message' => new MessageResource($this->whenLoaded('lastMessage')),
            'last_message_at' => $this->last_message_at,
            'unread_count' => $this->when(auth()->check(), function() {
                return $this->messages()
                    ->where('sender_id', '!=', auth()->id())
                    ->whereNull('read_at')
                    ->count();
            }),
            'created_at' => $this->created_at
        ];
    }
}