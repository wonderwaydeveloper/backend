<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function find(int $id): ?User;
    
    public function findByEmail(string $email): ?User;
    
    public function findByUsername(string $username): ?User;
    
    public function create(array $data): User;
    
    public function update(User $user, array $data): bool;
    
    public function delete(User $user): bool;
    
    public function getFollowers(int $userId, int $limit = 20): LengthAwarePaginator;
    
    public function getFollowing(int $userId, int $limit = 20): LengthAwarePaginator;
    
    public function searchUsers(string $query, int $limit = 20): array;
}