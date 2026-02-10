<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserUpdateDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $display_name = null,
        public readonly ?string $bio = null,
        public readonly ?string $location = null,
        public readonly ?string $website = null,
        public readonly ?string $avatar = null,
        public readonly ?string $cover = null,
        public readonly ?string $profile_link_color = null,
        public readonly ?string $profile_text_color = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?int $pinned_post_id = null,
        public readonly ?string $allow_dms_from = null,
        public readonly ?bool $quality_filter = null,
        public readonly ?bool $allow_sensitive_media = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            display_name: $request->display_name,
            bio: $request->bio,
            location: $request->location,
            website: $request->website,
            avatar: $request->avatar,
            cover: $request->cover,
            profile_link_color: $request->profile_link_color,
            profile_text_color: $request->profile_text_color,
            dateOfBirth: $request->date_of_birth,
            pinned_post_id: $request->pinned_post_id,
            allow_dms_from: $request->allow_dms_from,
            quality_filter: $request->quality_filter,
            allow_sensitive_media: $request->allow_sensitive_media
        );
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            display_name: $data['display_name'] ?? null,
            bio: $data['bio'] ?? null,
            location: $data['location'] ?? null,
            website: $data['website'] ?? null,
            avatar: $data['avatar'] ?? null,
            cover: $data['cover'] ?? null,
            profile_link_color: $data['profile_link_color'] ?? null,
            profile_text_color: $data['profile_text_color'] ?? null,
            dateOfBirth: $data['date_of_birth'] ?? null,
            pinned_post_id: $data['pinned_post_id'] ?? null,
            allow_dms_from: $data['allow_dms_from'] ?? null,
            quality_filter: $data['quality_filter'] ?? null,
            allow_sensitive_media: $data['allow_sensitive_media'] ?? null
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'avatar' => $this->avatar,
            'cover' => $this->cover,
            'profile_link_color' => $this->profile_link_color,
            'profile_text_color' => $this->profile_text_color,
            'date_of_birth' => $this->dateOfBirth,
            'pinned_post_id' => $this->pinned_post_id,
            'allow_dms_from' => $this->allow_dms_from,
            'quality_filter' => $this->quality_filter,
            'allow_sensitive_media' => $this->allow_sensitive_media,
        ], fn($value) => $value !== null);
    }
}