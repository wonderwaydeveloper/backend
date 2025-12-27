<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_type' => $this->device_type,
            'device_name' => $this->device_name,
            'active' => $this->active,
            'last_used_at' => $this->last_used_at,
            'created_at' => $this->created_at
        ];
    }
}