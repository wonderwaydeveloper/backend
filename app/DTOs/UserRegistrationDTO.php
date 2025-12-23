<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class UserRegistrationDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $phone = null,
        public readonly ?string $dateOfBirth = null,
        public readonly ?string $bio = null,
        public readonly ?string $avatar = null
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->name,
            username: $request->username,
            email: $request->email,
            password: $request->password,
            phone: $request->phone,
            dateOfBirth: $request->date_of_birth,
            bio: $request->bio,
            avatar: $request->avatar
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'phone' => $this->phone,
            'date_of_birth' => $this->dateOfBirth,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
        ];
    }
}