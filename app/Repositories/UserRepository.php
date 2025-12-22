<?php

namespace App\Repositories;

use App\Contracts\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function create(array $data): User
    {
        return User::create($data);
    }
    
    public function findById(int $id): ?User
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
    
    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
    
    public function delete(User $user): bool
    {
        return $user->delete();
    }
    
    public function getUserWithCounts(int $id): ?User
    {
        return User::withCount('posts', 'followers', 'following')->find($id);
    }
    
    public function getUserPosts(int $userId): LengthAwarePaginator
    {
        return User::findOrFail($userId)
            ->posts()
            ->with('user:id,name,username,avatar')
            ->withCount('likes', 'comments')
            ->latest()
            ->paginate(20);
    }
    
    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->select('id', 'name', 'username', 'avatar')
            ->limit($limit)
            ->get();
    }
}