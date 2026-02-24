<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserFollowService;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(
        protected UserFollowService $followService
    ) {}

    public function follow(User $user)
    {
        $this->authorize('follow', $user);
        
        $this->followService->follow(auth()->user(), $user);

        return response()->json(['message' => 'User followed successfully']);
    }

    public function unfollow(User $user)
    {
        $this->authorize('unfollow', $user);
        
        $this->followService->unfollow(auth()->user(), $user);

        return response()->json(['message' => 'User unfollowed successfully']);
    }

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
