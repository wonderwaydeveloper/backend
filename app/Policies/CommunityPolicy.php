<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

class CommunityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Community $community): bool
    {
        if ($community->privacy === 'public') {
            return true;
        }

        return $community->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Community $community): bool
    {
        $role = $community->getUserRole($user);
        return in_array($role, ['admin', 'owner']);
    }

    public function delete(User $user, Community $community): bool
    {
        return $community->getUserRole($user) === 'owner';
    }

    public function moderate(User $user, Community $community): bool
    {
        return $community->canUserModerate($user);
    }

    public function post(User $user, Community $community): bool
    {
        return $community->canUserPost($user);
    }

    public function pin(User $user, Community $community): bool
    {
        return $community->canUserModerate($user);
    }
}