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
            'plan_id' => $this->plan_id,
            'plan_name' => $this->plan_name,
            'status' => $this->status,
            'billing_cycle' => $this->billing_cycle,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'auto_renew' => $this->auto_renew,
            'started_at' => $this->started_at,
            'expires_at' => $this->expires_at,
            'cancelled_at' => $this->cancelled_at,
            'features' => $this->features,
            'is_active' => $this->expires_at > now() && $this->status === 'active',
            'days_remaining' => $this->expires_at ? $this->expires_at->diffInDays(now()) : null
        ];
    }
}