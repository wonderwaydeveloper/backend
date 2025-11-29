<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
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
            'is_edited' => $this->is_edited,
            'like_count' => $this->like_count,
            'reply_count' => $this->reply_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'edited_at' => $this->when($this->is_edited, $this->updated_at?->toISOString()),
            
            // نویسنده
            'user' => new UserResource($this->whenLoaded('user')),
            
            // مدیا
            'media' => CommentMediaResource::collection($this->whenLoaded('media')),
            
            // والد برای پاسخ‌ها
            'parent' => new CommentResource($this->whenLoaded('parent')),
            
            // پاسخ‌ها
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            
            // تعاملات کاربر جاری
            'interactions' => $this->when(auth()->check(), function () {
                return [
                    'liked' => $this->likes->contains('user_id', auth()->id()),
                ];
            }),
            
            // اطلاعات محتوای اصلی
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
        ];
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'can_reply' => auth()->check() && auth()->user()->can('reply', $this->resource),
                'can_edit' => auth()->check() && auth()->user()->can('update', $this->resource),
            ],
        ];
    }
}