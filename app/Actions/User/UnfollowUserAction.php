<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\Follow;

class UnfollowUserAction
{
    public function execute(User $follower, User $following): bool
    {
        return Follow::where('follower_id', $follower->id)
            ->where('following_id', $following->id)
            ->delete() > 0;
    }
}