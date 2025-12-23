<?php

namespace App\Actions\User;

use App\DTOs\UserUpdateDTO;
use App\Models\User;

class UpdateUserProfileAction
{
    public function execute(User $user, UserUpdateDTO $dto): User
    {
        $user->update([
            'name' => $dto->name ?? $user->name,
            'bio' => $dto->bio ?? $user->bio,
            'location' => $dto->location ?? $user->location,
            'website' => $dto->website ?? $user->website,
            'avatar' => $dto->avatar ?? $user->avatar,
        ]);

        return $user->fresh();
    }
}