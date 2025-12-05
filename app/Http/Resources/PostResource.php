<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Post;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // اگر resource یک آرایه است (مثلاً از کش)
        if (is_array($this->resource)) {
            return $this->transformArray($this->resource, $request);
        }

        // اگر resource یک مدل Post است
        if ($this->resource instanceof Post) {
            return $this->transformModel($this->resource, $request);
        }

        // حالت پیش‌فرض
        return [
            'id' => $this->id ?? null,
            'content' => $this->content ?? null,
            'type' => $this->type ?? 'post',
            'is_sensitive' => $this->is_sensitive ?? false,
            'is_edited' => $this->is_edited ?? false,
            'like_count' => $this->like_count ?? 0,
            'reply_count' => $this->reply_count ?? 0,
            'repost_count' => $this->repost_count ?? 0,
            'view_count' => $this->view_count ?? 0,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->user ?? null,
            'media' => $this->media ?? [],
            'interactions' => $this->getInteractions($request),
        ];
    }

    private function transformArray(array $data, Request $request): array
    {
        return [
            'id' => $data['id'] ?? null,
            'content' => $data['content'] ?? null,
            'type' => $data['type'] ?? 'post',
            'is_sensitive' => $data['is_sensitive'] ?? false,
            'is_edited' => $data['is_edited'] ?? false,
            'like_count' => $data['like_count'] ?? 0,
            'reply_count' => $data['reply_count'] ?? 0,
            'repost_count' => $data['repost_count'] ?? 0,
            'view_count' => $data['view_count'] ?? 0,
            'published_at' => $data['published_at'] ?? null,
            'created_at' => $data['created_at'] ?? null,
            'updated_at' => $data['updated_at'] ?? null,
            'user' => isset($data['user']) ? [
                'id' => $data['user']['id'] ?? null,
                'name' => $data['user']['name'] ?? null,
                'username' => $data['user']['username'] ?? null,
                'avatar' => $data['user']['avatar'] ?? null,
            ] : null,
            'media' => isset($data['media']) && is_array($data['media'])
                ? array_map(function ($media) {
                    return [
                        'id' => $media['id'] ?? null,
                        'file_path' => $media['file_path'] ?? null,
                        'type' => $media['type'] ?? null,
                    ];
                }, $data['media'])
                : [],
            'interactions' => $this->getInteractionsFromArray($data, $request),
        ];
    }

    private function transformModel(Post $post, Request $request): array
    {
        return [
            'id' => $post->id,
            'content' => $post->content,
            'type' => $post->type,
            'is_sensitive' => $post->is_sensitive,
            'is_edited' => $post->is_edited,
            'like_count' => $post->like_count,
            'reply_count' => $post->reply_count,
            'repost_count' => $post->repost_count,
            'view_count' => $post->view_count,
            'published_at' => $post->published_at?->toISOString(),
            'created_at' => $post->created_at?->toISOString(),
            'updated_at' => $post->updated_at?->toISOString(),

            // نویسنده
            'user' => $post->relationLoaded('user') ? [
                'id' => $post->user->id,
                'name' => $post->user->name,
                'username' => $post->user->username,
                'avatar' => $post->user->avatar,
            ] : null,

            // مدیا
            'media' => $post->relationLoaded('media')
                ? $post->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'file_path' => $media->file_path,
                        'type' => $media->type,
                    ];
                })->toArray()
                : [],

            // تعاملات کاربر جاری
            'interactions' => $this->getInteractionsFromModel($post, $request),
        ];
    }

    private function getInteractions(Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        // اگر resource یک آرایه است
        if (is_array($this->resource)) {
            return [
                'liked' => $this->resource['liked'] ?? false,
                'bookmarked' => $this->resource['bookmarked'] ?? false,
            ];
        }

        // اگر resource یک مدل است
        if ($this->resource instanceof Post) {
            return [
                'liked' => $this->resource->likes->contains('user_id', $user->id),
                'bookmarked' => method_exists($this->resource, 'isBookmarkedBy')
                    ? $this->resource->isBookmarkedBy($user)
                    : false,
            ];
        }

        return [];
    }

    private function getInteractionsFromArray(array $data, Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        return [
            'liked' => $data['liked'] ?? false,
            'bookmarked' => $data['bookmarked'] ?? false,
        ];
    }

    private function getInteractionsFromModel(Post $post, Request $request): array
    {
        $user = $request->user();
        if (!$user) {
            return [];
        }

        return [
            'liked' => $post->likes->contains('user_id', $user->id),
            'bookmarked' => method_exists($post, 'isBookmarkedBy')
                ? $post->isBookmarkedBy($user)
                : false,
        ];
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        $isSensitive = false;
        $canInteract = false;

        if (is_array($this->resource)) {
            $isSensitive = $this->resource['is_sensitive'] ?? false;
        } elseif ($this->resource instanceof Post) {
            $isSensitive = $this->resource->is_sensitive;
        }

        if (auth()->check()) {
            $user = auth()->user();
            if ($this->resource instanceof Post) {
                $canInteract = $user->can('like', $this->resource);
            }
        }

        return [
            'meta' => [
                'sensitive_content_warning' => $isSensitive,
                'can_interact' => $canInteract,
            ],
        ];
    }
}