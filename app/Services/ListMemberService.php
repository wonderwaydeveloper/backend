<?php

namespace App\Services;

use App\Models\{UserList, User};
use App\Contracts\Repositories\ListMemberRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ListMemberService
{
    public function __construct(
        private ListMemberRepositoryInterface $memberRepository
    ) {}

    public function addMember(UserList $list, User $user, int $targetUserId): array
    {
        // Block/Mute check
        $targetUser = User::findOrFail($targetUserId);
        
        if ($list->owner->hasBlocked($targetUserId) || $targetUser->hasBlocked($list->user_id)) {
            throw new \Exception('Cannot add blocked user to list');
        }

        return DB::transaction(function () use ($list, $user, $targetUserId) {
            $result = $this->memberRepository->addMember($list, $targetUserId);
            
            if ($result['added']) {
                broadcast(new \App\Events\ListMemberAdded($list, User::find($targetUserId)));
            }

            return $result;
        });
    }

    public function removeMember(UserList $list, int $userId): void
    {
        DB::transaction(function () use ($list, $userId) {
            $this->memberRepository->removeMember($list, $userId);
            broadcast(new \App\Events\ListMemberRemoved($list, User::find($userId)));
        });
    }

    public function getMembers(UserList $list, int $perPage = 20)
    {
        return $this->memberRepository->getMembers($list, $perPage);
    }
}
