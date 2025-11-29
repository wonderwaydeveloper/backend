<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this['user']),
            'access_token' => $this['token'],
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
            'two_factor_required' => $this['two_factor_required'] ?? false,
        ];
    }

    /**
     * داده‌های اضافی
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'message' => $this['message'] ?? 'Successfully authenticated',
            ],
        ];
    }
}