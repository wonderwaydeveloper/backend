<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class FollowController extends Controller
{
    public function followers(User $user)
    {
        $this->authorize('view', $user);
        
        $followers = $user->followers()
            ->select('users.id', 'users.name', 'users.username', 'users.avatar')
            ->paginate(config('limits.pagination.follows'));

        return response()->json($followers);
    }

    public function following(User $user)
    {
        $this->authorize('view', $user);
        
        $following = $user->following()
            ->select('users.id', 'users.name', 'users.username', 'users.avatar')
            ->paginate(config('limits.pagination.follows'));

        return response()->json($following);
    }
}
