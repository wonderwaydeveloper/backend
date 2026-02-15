<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cpu_usage' => $this->resource['cpu_usage'] ?? null,
            'memory_usage' => $this->resource['memory_usage'] ?? null,
            'disk_usage' => $this->resource['disk_usage'] ?? null,
            'response_time' => $this->resource['response_time'] ?? null,
            'throughput' => $this->resource['throughput'] ?? null,
            'cache_hit_rate' => $this->resource['cache_hit_rate'] ?? null,
            'database_queries' => $this->resource['database_queries'] ?? null,
            'active_connections' => $this->resource['active_connections'] ?? null,
            'timestamp' => isset($this->resource['timestamp']) ? $this->resource['timestamp']->toIso8601String() : now()->toIso8601String(),
        ];
    }
}
