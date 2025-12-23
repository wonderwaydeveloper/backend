<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DTOs\UserRegistrationDTO;
use App\DTOs\UserUpdateDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function create(UserRegistrationDTO $dto): User
    {
        return User::create($dto->toArray());
    }

    public function update(int $id, UserUpdateDTO $dto): User
    {
        $user = User::findOrFail($id);
        $user->update($dto->toArray());
        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        return User::destroy($id) > 0;
    }

    public function getFollowers(int $userId): LengthAwarePaginator
    {
        return User::whereHas('following', function ($query) use ($userId) {
            $query->where('following_id', $userId);
        })->paginate(20);
    }

    public function getFollowing(int $userId): LengthAwarePaginator
    {
        return User::whereHas('followers', function ($query) use ($userId) {
            $query->where('follower_id', $userId);
        })->paginate(20);
    }

    public function search(string $query): LengthAwarePaginator
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->paginate(20);
    }

    public function getSuggestions(int $userId, int $limit = 10): Collection
    {
        return User::where('id', '!=', $userId)
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $userId);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}