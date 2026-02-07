<?php

namespace App\Policies;

use App\Models\Space;
use App\Models\User;

class SpacePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Space $space): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function update(User $user, Space $space): bool
    {
        return $user->id === $space->host_id;
    }

    public function delete(User $user, Space $space): bool
    {
        return $user->id === $space->host_id;
    }

    public function host(User $user, Space $space): bool
    {
        return $user->id === $space->host_id;
    }

    public function speak(User $user, Space $space): bool
    {
        // Host can always speak
        if ($user->id === $space->host_id) {
            return true;
        }

        // Check if user is a speaker
        return $space->speakers()->where('user_id', $user->id)->exists();
    }
}
