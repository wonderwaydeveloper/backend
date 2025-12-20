<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    public function searchUsers(string $query, int $perPage = null): LengthAwarePaginator
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->select('id', 'name', 'username', 'avatar')
            ->paginate($perPage ?? config('pagination.users'));
    }

    public function getFollowers(User $user, int $perPage = null): LengthAwarePaginator
    {
        return $user->followers()
            ->select('users.id', 'users.name', 'users.username', 'users.avatar')
            ->paginate($perPage ?? config('pagination.users'));
    }

    public function getFollowing(User $user, int $perPage = null): LengthAwarePaginator
    {
        return $user->following()
            ->select('users.id', 'users.name', 'users.username', 'users.avatar')
            ->paginate($perPage ?? config('pagination.users'));
    }
}
