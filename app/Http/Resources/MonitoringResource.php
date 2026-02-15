<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonitoringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'metric_name' => $this->resource['metric_name'] ?? null,
            'value' => $this->resource['value'] ?? null,
            'unit' => $this->resource['unit'] ?? null,
            'status' => $this->resource['status'] ?? 'normal',
            'threshold' => $this->resource['threshold'] ?? null,
            'alert_level' => $this->resource['alert_level'] ?? null,
            'timestamp' => isset($this->resource['timestamp']) ? $this->resource['timestamp']->toIso8601String() : now()->toIso8601String(),
        ];
    }
}
