<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($this->shouldIncludeEmail(), $this->email),
            'phone' => $this->when($this->shouldIncludePhone(), $this->phone),
            'bio' => $this->bio,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'cover_image' => $this->cover_image ? asset('storage/' . $this->cover_image) : null,
            'website' => $this->website,
            'location' => $this->location,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age,
            'is_private' => $this->is_private,
            'is_verified' => $this->is_verified,
            'is_underage' => $this->is_underage,
            'followers_count' => $this->followers_count,
            'following_count' => $this->following_count,
            'posts_count' => $this->posts_count,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // روابط
            'is_following' => $this->whenLoaded('followers', function () {
                return $this->followers->contains('id', auth()->id());
            }),
            'is_followed_by' => $this->whenLoaded('following', function () {
                return $this->following->contains('id', auth()->id());
            }),
            'followers' => UserResource::collection($this->whenLoaded('followers')),
            'following' => UserResource::collection($this->whenLoaded('following')),
            
            // اطلاعات امنیتی (فقط برای کاربر خودش)
            'two_factor_enabled' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                $this->two_factor_enabled
            ),
            'last_login_at' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                $this->last_login_at?->toISOString()
            ),
        ];
    }

    /**
     * بررسی آیا باید ایمیل را شامل شود
     */
    private function shouldIncludeEmail(): bool
    {
        return auth()->check() && (auth()->id() === $this->id || auth()->user()->username === 'admin');
    }

    /**
     * بررسی آیا باید شماره تلفن را شامل شود
     */
    private function shouldIncludePhone(): bool
    {
        return auth()->check() && (auth()->id() === $this->id || auth()->user()->username === 'admin');
    }

    /**
     * داده‌های اضافی که با ریسورس برگردانده می‌شود
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'api_version' => 'v1',
            ],
        ];
    }
}