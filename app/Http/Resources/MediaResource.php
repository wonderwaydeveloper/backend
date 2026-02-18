<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'type' => $this->type,
            'url' => $this->url,
            'thumbnail_url' => $this->thumbnail_url,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'duration' => $this->duration,
            'alt_text' => $this->alt_text,
            'encoding_status' => $this->encoding_status,
            'processing_progress' => $this->processing_progress,
            'created_at' => $this->created_at,
        ];

        if ($this->type === 'image' && $this->image_variants) {
            $data['variants'] = $this->image_variants;
        }

        if ($this->type === 'video' && $this->video_qualities) {
            $data['qualities'] = $this->video_qualities;
        }

        return $data;
    }
}
