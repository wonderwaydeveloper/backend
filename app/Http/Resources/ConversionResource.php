<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'conversion_type' => $this->conversion_type,
            'conversion_value' => $this->conversion_value,
            'source' => $this->source,
            'campaign' => $this->campaign,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
