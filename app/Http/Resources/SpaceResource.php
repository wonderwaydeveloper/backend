<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'is_public' => $this->is_public,
            'status' => $this->status,
            'participant_count' => $this->participant_count ?? 0,
            'max_participants' => $this->max_participants,
            'scheduled_at' => $this->scheduled_at,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'host' => new UserResource($this->whenLoaded('host')),
            'participants' => UserResource::collection($this->whenLoaded('participants'))
        ];
    }
}