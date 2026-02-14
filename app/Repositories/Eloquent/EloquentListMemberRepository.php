<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ListMemberRepositoryInterface;
use App\Models\UserList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentListMemberRepository implements ListMemberRepositoryInterface
{
    public function addMember(UserList $list, int $userId): array
    {
        if ($this->hasMember($list, $userId)) {
            return ['added' => false, 'message' => 'User already in list'];
        }

        $list->members()->attach($userId);
        $list->increment('members_count');

        return ['added' => true, 'message' => 'Member added successfully'];
    }

    public function removeMember(UserList $list, int $userId): void
    {
        if (!$this->hasMember($list, $userId)) {
            throw new \Exception('User not in list');
        }

        $list->members()->detach($userId);
        $list->decrement('members_count');
    }

    public function hasMember(UserList $list, int $userId): bool
    {
        return $list->members()->where('user_id', $userId)->exists();
    }

    public function getMembers(UserList $list, int $perPage = 20): LengthAwarePaginator
    {
        return $list->members()
            ->select(['users.id', 'name', 'username', 'avatar'])
            ->paginate($perPage);
    }
}
