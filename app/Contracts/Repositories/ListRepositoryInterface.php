<?php

namespace App\Contracts\Repositories;

use App\Models\UserList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListRepositoryInterface
{
    public function create(array $data): UserList;
    
    public function update(UserList $list, array $data): UserList;
    
    public function delete(UserList $list): bool;
    
    public function findById(int $id): ?UserList;
    
    public function getUserLists(int $userId, int $perPage = 20): LengthAwarePaginator;
    
    public function getPublicLists(int $perPage = 20): LengthAwarePaginator;
    
    public function subscribe(UserList $list, int $userId): array;
    
    public function unsubscribe(UserList $list, int $userId): void;
    
    public function isSubscribed(UserList $list, int $userId): bool;
}
