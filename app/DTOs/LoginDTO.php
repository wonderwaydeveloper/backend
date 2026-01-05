<?php

namespace App\DTOs;

class LoginDTO
{
    public function __construct(
        public readonly string $login,
        public readonly string $password,
        public readonly ?string $twoFactorCode = null
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            login: $data['login'],
            password: $data['password'],
            twoFactorCode: $data['two_factor_code'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'password' => $this->password,
            'two_factor_code' => $this->twoFactorCode,
        ];
    }
}