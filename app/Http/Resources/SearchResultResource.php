<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->getType(),
            'content' => $this->getContent(),
            'user' => $this->when(isset($this->user), new UserResource($this->user)),
            'metadata' => $this->getMetadata(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function getType()
    {
        return class_basename($this->resource);
    }

    private function getContent()
    {
        if (isset($this->content)) {
            return $this->content;
        }
        if (isset($this->name)) {
            return $this->name;
        }
        return null;
    }

    private function getMetadata()
    {
        $metadata = [];
        
        if (isset($this->likes_count)) {
            $metadata['likes_count'] = $this->likes_count;
        }
        if (isset($this->comments_count)) {
            $metadata['comments_count'] = $this->comments_count;
        }
        if (isset($this->posts_count)) {
            $metadata['posts_count'] = $this->posts_count;
        }
        if (isset($this->followers_count)) {
            $metadata['followers_count'] = $this->followers_count;
        }
        
        return $metadata;
    }
}
