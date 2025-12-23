<?php

namespace App\Actions\User;

use App\Models\User;
use App\Models\Follow;

class FollowUserAction
{
    public function execute(User $follower, User $following): Follow
    {
        return Follow::firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]);
    }
}