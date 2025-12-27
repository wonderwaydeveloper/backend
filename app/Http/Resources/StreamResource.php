<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StreamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'stream_key' => $this->stream_key,
            'rtmp_url' => $this->rtmp_url ?? 'rtmp://localhost/live/' . $this->stream_key,
            'status' => $this->status,
            'category' => $this->category,
            'is_private' => $this->is_private,
            'viewer_count' => $this->viewer_count ?? 0,
            'max_viewers' => $this->max_viewers,
            'thumbnail' => $this->thumbnail,
            'tags' => $this->tags,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'created_at' => $this->created_at,
            'streamer' => new UserResource($this->whenLoaded('user'))
        ];
    }
}