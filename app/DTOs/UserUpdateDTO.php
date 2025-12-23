<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserUpdateDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $bio = null,
        public readonly ?string $location = null,
        public readonly ?string $website = null,
        public readonly ?string $avatar = null,
        public readonly ?string $banner = null,
        public readonly ?array $privacySettings = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            username: $request->username,
            email: $request->email,
            bio: $request->bio,
            location: $request->location,
            website: $request->website,
            avatar: $request->avatar,
            banner: $request->banner,
            privacySettings: $request->privacy_settings
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'avatar' => $this->avatar,
            'banner' => $this->banner,
            'privacy_settings' => $this->privacySettings,
        ], fn($value) => $value !== null);
    }
}