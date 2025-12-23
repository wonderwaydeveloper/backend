<?php

namespace App\Actions\User;

use App\DTOs\UserRegistrationDTO;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function execute(UserRegistrationDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'username' => $dto->username,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'bio' => $dto->bio,
            'location' => $dto->location,
            'website' => $dto->website,
            'birth_date' => $dto->birthDate,
        ]);
    }
}