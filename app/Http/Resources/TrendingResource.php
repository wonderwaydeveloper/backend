<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TrendingResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? null,
            'slug' => $this->slug ?? null,
            'content' => $this->content ?? null,
            'user' => $this->when(isset($this->user), new UserResource($this->user)),
            'trend_score' => $this->trend_score ?? $this->engagement_score ?? $this->personalized_score ?? 0,
            'recent_posts_count' => $this->recent_posts_count ?? null,
            'engagement_score' => $this->engagement_score ?? null,
            'posts_count' => $this->posts_count ?? null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
