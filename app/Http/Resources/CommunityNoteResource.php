<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunityNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'sources' => $this->sources,
            'helpful_votes' => $this->helpful_votes ?? 0,
            'not_helpful_votes' => $this->not_helpful_votes ?? 0,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'author' => new UserResource($this->whenLoaded('author')),
            'post' => new PostResource($this->whenLoaded('post')),
            'user_vote' => $this->when(auth()->check(), function() {
                return $this->votes()->where('user_id', auth()->id())->first()?->vote_type;
            })
        ];
    }
}