<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\ListRepositoryInterface;
use App\Models\UserList;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentListRepository implements ListRepositoryInterface
{
    public function create(array $data): UserList
    {
        return UserList::create($data);
    }

    public function update(UserList $list, array $data): UserList
    {
        $list->update($data);
        return $list->fresh();
    }

    public function delete(UserList $list): bool
    {
        return $list->delete();
    }

    public function findById(int $id): ?UserList
    {
        return UserList::with(['owner:id,name,username,avatar', 'members:id,name,username,avatar'])
            ->withCount(['members', 'subscribers'])
            ->find($id);
    }

    public function getUserLists(int $userId, int $perPage = null): LengthAwarePaginator
    {
        return UserList::where('user_id', $userId)
            ->withCount(['members', 'subscribers'])
            ->latest()
            ->paginate($perPage ?? config('pagination.lists'));
    }

    public function getPublicLists(int $perPage = null): LengthAwarePaginator
    {
        return UserList::where('privacy', 'public')
            ->with(['owner:id,name,username,avatar'])
            ->withCount(['members', 'subscribers'])
            ->orderBy('subscribers_count', 'desc')
            ->paginate($perPage ?? config('pagination.lists'));
    }

    public function subscribe(UserList $list, int $userId): array
    {
        if ($this->isSubscribed($list, $userId)) {
            return ['subscribed' => false, 'message' => 'Already subscribed'];
        }

        $list->subscribers()->attach($userId);
        $list->increment('subscribers_count');

        return ['subscribed' => true, 'message' => 'Subscribed successfully'];
    }

    public function unsubscribe(UserList $list, int $userId): void
    {
        if (!$this->isSubscribed($list, $userId)) {
            throw new \Exception('Not subscribed');
        }

        $list->subscribers()->detach($userId);
        $list->decrement('subscribers_count');
    }

    public function isSubscribed(UserList $list, int $userId): bool
    {
        return $list->subscribers()->where('user_id', $userId)->exists();
    }
}
