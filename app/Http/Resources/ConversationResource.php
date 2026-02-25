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
            'type' => $this->type,
            'name' => $this->when($this->isGroup(), $this->name),
            'user_one_id' => $this->when($this->isDirect(), $this->user_one_id),
            'user_two_id' => $this->when($this->isDirect(), $this->user_two_id),
            'user_one' => $this->when($this->isDirect(), new UserResource($this->whenLoaded('userOne'))),
            'user_two' => $this->when($this->isDirect(), new UserResource($this->whenLoaded('userTwo'))),
            'participants' => $this->when($this->isGroup(), UserResource::collection($this->whenLoaded('activeParticipants'))),
            'participants_count' => $this->when($this->isGroup(), $this->activeParticipants()->count()),
            'max_participants' => $this->when($this->isGroup(), $this->max_participants),
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