<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'media_url' => $this->media_url,
            'media_type' => $this->media_type,
            'duration' => $this->duration,
            'background_color' => $this->background_color,
            'font_style' => $this->font_style,
            'is_close_friends' => $this->is_close_friends,
            'view_count' => $this->view_count ?? 0,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->expires_at < now(),
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
            'has_viewed' => $this->when(auth()->check(), function() {
                return $this->views()->where('user_id', auth()->id())->exists();
            })
        ];
    }
}