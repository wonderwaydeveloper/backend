<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Poll $poll): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('poll.create');
    }

    public function vote(User $user, Poll $poll): bool
    {
        return $user->hasPermissionTo('poll.vote');
    }

    public function delete(User $user, Poll $poll): bool
    {
        $isOwner = $poll->post->user_id === $user->id;
        return $isOwner && $user->hasPermissionTo('poll.delete.own');
    }
}
