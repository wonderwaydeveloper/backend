<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($this->shouldIncludeContent(), $this->content),
            'featured_image' => $this->featured_image ? asset('storage/' . $this->featured_image) : null,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'is_approved' => $this->is_approved,
            'view_count' => $this->view_count,
            'like_count' => $this->like_count,
            'comment_count' => $this->comment_count,
            'share_count' => $this->share_count,
            'reading_time' => $this->reading_time,
            'tags' => $this->tags,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // نویسنده
            'user' => new UserResource($this->whenLoaded('user')),
            
            // مدیا
            'media' => ArticleMediaResource::collection($this->whenLoaded('media')),
            
            // تایید کننده
            'approver' => new UserResource($this->whenLoaded('approver')),
            
            // تعاملات کاربر جاری
            'interactions' => $this->when(auth()->check(), function () {
                return [
                    'liked' => $this->likes->contains('user_id', auth()->id()),
                    'bookmarked' => $this->bookmarks->contains('user_id', auth()->id()),
                ];
            }),
            
            // اطلاعات زمان‌بندی (فقط برای نویسنده و ادمین)
            'scheduled_at' => $this->when(
                $this->shouldIncludeSchedulingInfo(),
                $this->scheduled_at?->toISOString()
            ),
            'approved_at' => $this->when(
                $this->shouldIncludeApprovalInfo(),
                $this->approved_at?->toISOString()
            ),
        ];
    }

    /**
     * بررسی آیا باید محتوا شامل شود
     */
    private function shouldIncludeContent(): bool
    {
        // برای مقالات منتشر شده یا برای نویسنده/ادمین
        return $this->status === 'published' || 
               (auth()->check() && (
                   auth()->id() === $this->user_id || 
                   auth()->user()->username === 'admin'
               ));
    }

    /**
     * بررسی آیا باید اطلاعات زمان‌بندی شامل شود
     */
    private function shouldIncludeSchedulingInfo(): bool
    {
        return auth()->check() && (
            auth()->id() === $this->user_id || 
            auth()->user()->username === 'admin'
        );
    }

    /**
     * بررسی آیا باید اطلاعات تایید شامل شود
     */
    private function shouldIncludeApprovalInfo(): bool
    {
        return auth()->check() && (
            auth()->id() === $this->user_id || 
            auth()->user()->username === 'admin' ||
            auth()->id() === $this->approved_by
        );
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'reading_progress' => $this->reading_time,
                'can_comment' => auth()->check() && auth()->user()->can('comment', $this->resource),
            ],
        ];
    }
}