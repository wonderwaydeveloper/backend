<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PremiumResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plan' => $this->plan,
            'price' => $this->price,
            'billing_cycle' => $this->billing_cycle,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'transaction_id' => $this->transaction_id,
            'features' => $this->features,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}