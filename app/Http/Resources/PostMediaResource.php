<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostMediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_url' => $this->url,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'type' => $this->type,
            'duration' => $this->duration,
            'thumbnail_url' => $this->thumbnail ? asset('storage/' . $this->thumbnail) : null,
            'order' => $this->order,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toISOString(),
            
            // اطلاعات اضافی
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_gif' => $this->isGif(),
        ];
    }
}