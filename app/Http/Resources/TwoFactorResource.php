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
        // ابتدا تمام داده‌ها را در یک متغیر بریزید تا از خطاهای دسترسی جلوگیری شود
        $data = $this->resource;

        return [
            'enabled' => $data['enabled'] ?? false,
            'qr_code' => $data['qr_code'] ?? null,
            'recovery_codes' => $data['recovery_codes'] ?? null,
            'message' => $data['message'] ?? null,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with(Request $request): array
    {
        $data = $this->resource;
        $recoveryCodes = $data['recovery_codes'] ?? null;

        return [
            'meta' => [
                'backup_codes_count' => is_array($recoveryCodes) ? count($recoveryCodes) : 0,
            ],
        ];
    }
}