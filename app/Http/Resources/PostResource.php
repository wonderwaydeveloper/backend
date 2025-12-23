<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Handle both Model and Array data
        $data = is_array($this->resource) ? $this->resource : $this->resource->toArray();
        
        return [
            'id' => $data['id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'content' => $data['content'] ?? null,
            'image' => $data['image'] ?? null,
            'video' => $data['video'] ?? null,
            'gif_url' => $data['gif_url'] ?? null,
            'likes_count' => $data['likes_count'] ?? 0,
            'comments_count' => $data['comments_count'] ?? 0,
            'quotes_count' => $data['quotes_count'] ?? 0,
            'is_draft' => $data['is_draft'] ?? false,
            'is_edited' => $data['is_edited'] ?? false,
            'reply_settings' => $data['reply_settings'] ?? null,
            'published_at' => $data['published_at'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'thread_id' => $data['thread_id'] ?? null,
            'quoted_post_id' => $data['quoted_post_id'] ?? null,
            
            // Relations - only for Model objects
            'user' => $this->when(
                !is_array($this->resource) && $this->relationLoaded('user'),
                fn() => new UserResource($this->user)
            ),
            'hashtags' => $this->when(
                !is_array($this->resource) && $this->relationLoaded('hashtags'),
                fn() => HashtagResource::collection($this->hashtags)
            ),
            'quoted_post' => $this->when(
                !is_array($this->resource) && $this->relationLoaded('quotedPost'),
                fn() => new PostResource($this->quotedPost)
            ),
            
            // For array data, include user if present
            $this->mergeWhen(is_array($this->resource) && isset($data['user']), [
                'user' => $data['user'] ?? null
            ]),
            
            // Computed fields - only for Model objects
            'is_liked' => $this->when(
                !is_array($this->resource) && auth()->check(),
                fn() => $this->isLikedBy(auth()->id())
            ),
            'thread_info' => $this->when(
                !is_array($this->resource) && method_exists($this->resource, 'isThread') && $this->isThread(),
                fn() => [
                    'is_main_thread' => $this->isMainThread(),
                    'thread_position' => $this->thread_position,
                    'total_posts' => $this->threadPosts()->count() + 1,
                ]
            ),
        ];
    }
}