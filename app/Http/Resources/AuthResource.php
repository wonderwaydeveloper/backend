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
        // ارسال یک پرچم به UserResource برای نمایش ایمیل
        $userResource = new \App\Http\Resources\UserResource($this['user'], ['include_email' => true]);

        $data = [
            'user' => $userResource,
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null,
        ];

        // فقط در صورتی که توکن وجود داشت، آن را اضافه کن
        if (isset($this['token'])) {
            $data['access_token'] = $this['token'];
        }

        return $data;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
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