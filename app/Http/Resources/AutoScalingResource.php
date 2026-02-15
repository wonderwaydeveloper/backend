<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AutoScalingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'current_instances' => $this->resource['current_instances'] ?? null,
            'target_instances' => $this->resource['target_instances'] ?? null,
            'min_instances' => $this->resource['min_instances'] ?? 1,
            'max_instances' => $this->resource['max_instances'] ?? 10,
            'cpu_threshold' => $this->resource['cpu_threshold'] ?? 70,
            'memory_threshold' => $this->resource['memory_threshold'] ?? 80,
            'scaling_status' => $this->resource['scaling_status'] ?? 'stable',
            'last_scaled_at' => isset($this->resource['last_scaled_at']) ? $this->resource['last_scaled_at']->toIso8601String() : null,
            'timestamp' => isset($this->resource['timestamp']) ? $this->resource['timestamp']->toIso8601String() : now()->toIso8601String(),
        ];
    }
}
