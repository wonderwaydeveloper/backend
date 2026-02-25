<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_type' => $this->message_type,
            'content' => $this->content,
            'sender_id' => $this->sender_id,
            'attachments' => MediaResource::collection($this->whenLoaded('media')),
            'gif_url' => $this->gif_url,
            'voice_duration' => $this->when($this->isVoice(), $this->voice_duration),
            'is_forwarded' => $this->isForwarded(),
            'is_edited' => $this->isEdited(),
            'edited_at' => $this->edited_at,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'reactions' => $this->when($this->relationLoaded('reactions'), function() {
                return $this->reactions()
                    ->selectRaw('emoji, count(*) as count')
                    ->groupBy('emoji')
                    ->get()
                    ->mapWithKeys(fn($r) => [$r->emoji => $r->count]);
            }),
            'user_reaction' => $this->when(auth()->check(), function() {
                $reaction = $this->reactions()
                    ->where('user_id', auth()->id())
                    ->first();
                return $reaction ? $reaction->emoji : null;
            }),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
