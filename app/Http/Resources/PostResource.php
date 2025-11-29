<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
            'content' => $this->content,
            'type' => $this->type,
            'is_sensitive' => $this->is_sensitive,
            'is_edited' => $this->is_edited,
            'like_count' => $this->like_count,
            'reply_count' => $this->reply_count,
            'repost_count' => $this->repost_count,
            'view_count' => $this->view_count,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // نویسنده
            'user' => new UserResource($this->whenLoaded('user')),
            
            // مدیا
            'media' => PostMediaResource::collection($this->whenLoaded('media')),
            
            // پست والد برای پاسخ‌ها
            'parent' => new PostResource($this->whenLoaded('parent')),
            
            // پست اصلی برای بازنشرها
            'original_post' => new PostResource($this->whenLoaded('originalPost')),
            
            // تعاملات کاربر جاری
            'interactions' => $this->when(auth()->check(), function () {
                return [
                    'liked' => $this->likes->contains('user_id', auth()->id()),
                    'bookmarked' => $this->bookmarks->contains('user_id', auth()->id()),
                    'reposted' => $this->reposts->contains('user_id', auth()->id()) ?? false,
                ];
            }),
            
            // پاسخ‌ها
            'replies' => PostResource::collection($this->whenLoaded('replies')),
            
            // اطلاعات اضافی برای ادمین
            'scheduled_at' => $this->when(
                $request->user()?->username === 'admin',
                $this->scheduled_at?->toISOString()
            ),
            'deleted_at' => $this->when(
                $request->user()?->username === 'admin',
                $this->deleted_at?->toISOString()
            ),
        ];
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'sensitive_content_warning' => $this->is_sensitive,
                'can_interact' => auth()->check() && auth()->user()->can('like', $this->resource),
            ],
        ];
    }
}