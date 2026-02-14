<?php

namespace App\Contracts\Repositories;

use App\Models\UserList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ListMemberRepositoryInterface
{
    public function addMember(UserList $list, int $userId): array;
    
    public function removeMember(UserList $list, int $userId): void;
    
    public function hasMember(UserList $list, int $userId): bool;
    
    public function getMembers(UserList $list, int $perPage = 20): LengthAwarePaginator;
}
