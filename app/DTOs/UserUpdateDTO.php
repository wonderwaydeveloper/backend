<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserUpdateDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $bio = null,
        public readonly ?string $location = null,
        public readonly ?string $website = null,
        public readonly ?string $avatar = null,
        public readonly ?string $cover = null,
        public readonly ?string $dateOfBirth = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            bio: $request->bio,
            location: $request->location,
            website: $request->website,
            avatar: $request->avatar,
            cover: $request->cover,
            dateOfBirth: $request->date_of_birth
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'bio' => $this->bio,
            'location' => $this->location,
            'website' => $this->website,
            'avatar' => $this->avatar,
            'cover' => $this->cover,
            'date_of_birth' => $this->dateOfBirth,
        ], fn($value) => $value !== null);
    }
}