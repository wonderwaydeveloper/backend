<?php

namespace App\DTOs;

class UserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly ?\DateTime $dateOfBirth = null,
        public readonly ?string $phone = null,
        public readonly ?string $bio = null,
        public readonly bool $isPrivate = false,
        public readonly ?string $googleId = null,
        public readonly ?string $githubId = null,
        public readonly ?string $facebookId = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            name: $data['name'],
            username: $data['username'],
            email: $data['email'],
            password: $data['password'],
            dateOfBirth: isset($data['date_of_birth']) ? new \DateTime($data['date_of_birth']) : null,
            phone: $data['phone'] ?? null,
            bio: $data['bio'] ?? null,
            isPrivate: $data['is_private'] ?? false,
            googleId: $data['google_id'] ?? null,
            githubId: $data['github_id'] ?? null,
            facebookId: $data['facebook_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'date_of_birth' => $this->dateOfBirth?->format('Y-m-d'),
            'phone' => $this->phone,
            'bio' => $this->bio,
            'is_private' => $this->isPrivate,
            'google_id' => $this->googleId,
            'github_id' => $this->githubId,
            'facebook_id' => $this->facebookId,
        ];
    }
}