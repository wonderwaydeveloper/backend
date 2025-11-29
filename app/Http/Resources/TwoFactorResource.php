<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TwoFactorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'enabled' => $this['enabled'],
            'qr_code' => $this['qr_code'] ?? null,
            'recovery_codes' => $this['recovery_codes'] ?? null,
            'message' => $this['message'],
        ];
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'backup_codes_count' => $this['recovery_codes'] ? count($this['recovery_codes']) : 0,
            ],
        ];
    }
}