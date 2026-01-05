<?php

namespace App\DTOs;

class UserRegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $dateOfBirth,
        public readonly ?string $phone = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            username: $data['username'],
            email: $data['email'],
            password: $data['password'],
            dateOfBirth: $data['date_of_birth'],
            phone: $data['phone'] ?? null
        );
    }
}