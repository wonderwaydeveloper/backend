<?php

namespace App\Services;

use App\Models\{UserList, User};
use App\Contracts\Repositories\ListRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ListService
{
    public function __construct(
        private ListRepositoryInterface $listRepository,
        private ListMemberService $memberService
    ) {}

    public function createList(User $user, array $data): UserList
    {
        return DB::transaction(function () use ($user, $data) {
            $list = $this->listRepository->create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'privacy' => $data['privacy'] ?? 'public',
                'banner_image' => $data['banner_image'] ?? null,
            ]);

            broadcast(new \App\Events\ListCreated($list, $user));

            return $list->fresh(['owner']);
        });
    }

    public function updateList(UserList $list, array $data): UserList
    {
        return DB::transaction(function () use ($list, $data) {
            $this->listRepository->update($list, $data);
            return $list->fresh(['owner']);
        });
    }

    public function deleteList(UserList $list): void
    {
        DB::transaction(function () use ($list) {
            $this->listRepository->delete($list);
        });
    }

    public function subscribe(UserList $list, User $user): array
    {
        if ($list->privacy === 'private') {
            throw new \Exception('Cannot subscribe to private list');
        }

        // Block/Mute check
        if ($list->owner->hasBlocked($user->id) || $user->hasBlocked($list->owner->id)) {
            throw new \Exception('Cannot subscribe to this list');
        }

        return DB::transaction(function () use ($list, $user) {
            $result = $this->listRepository->subscribe($list, $user->id);
            
            if ($result['subscribed']) {
                broadcast(new \App\Events\ListSubscribed($list, $user));
            }

            return $result;
        });
    }

    public function unsubscribe(UserList $list, User $user): void
    {
        DB::transaction(function () use ($list, $user) {
            $this->listRepository->unsubscribe($list, $user->id);
        });
    }

    public function canView(UserList $list, User $user): bool
    {
        if ($list->privacy === 'public') {
            return true;
        }

        if ($list->user_id === $user->id) {
            return true;
        }

        // Block/Mute check
        if ($list->owner->hasBlocked($user->id) || $user->hasBlocked($list->owner->id)) {
            return false;
        }

        return false;
    }
}
