<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdvertisementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'advertiser_id' => $this->advertiser_id,
            'title' => $this->title,
            'content' => $this->content,
            'media_url' => $this->media_url,
            'target_audience' => $this->target_audience,
            'budget' => $this->budget,
            'cost_per_click' => $this->cost_per_click,
            'cost_per_impression' => $this->cost_per_impression,
            'start_date' => $this->start_date->toIso8601String(),
            'end_date' => $this->end_date->toIso8601String(),
            'status' => $this->status,
            'impressions_count' => $this->impressions_count,
            'clicks_count' => $this->clicks_count,
            'conversions_count' => $this->conversions_count,
            'total_spent' => $this->total_spent,
            'targeting_criteria' => $this->targeting_criteria,
            'ctr' => $this->getCTR(),
            'conversion_rate' => $this->getConversionRate(),
            'remaining_budget' => $this->getRemainingBudget(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
