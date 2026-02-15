<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreatorFundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'creator_id' => $this->creator_id,
            'month' => $this->month,
            'year' => $this->year,
            'total_views' => $this->total_views,
            'total_engagement' => $this->total_engagement,
            'quality_score' => $this->quality_score,
            'earnings' => $this->earnings,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'metrics' => $this->metrics,
            'is_eligible' => $this->isEligible(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
