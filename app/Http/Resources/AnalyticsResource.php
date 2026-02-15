<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'profile_views' => $this->resource['profile_views'] ?? null,
            'post_performance' => $this->resource['post_performance'] ?? null,
            'engagement_metrics' => $this->resource['engagement_metrics'] ?? null,
            'follower_growth' => $this->resource['follower_growth'] ?? null,
            'top_posts' => $this->resource['top_posts'] ?? null,
            'period' => $this->resource['period'] ?? null,
        ];
    }
}
