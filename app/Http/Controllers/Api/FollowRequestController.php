<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Http\Request;

class FollowRequestController extends Controller
{
    public function send(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Cannot send follow request to yourself',
            ], 400);
        }

        if ($currentUser->isFollowing($user->id)) {
            return response()->json([
                'message' => 'Already following this user',
            ], 400);
        }

        $existingRequest = FollowRequest::where('follower_id', $currentUser->id)
            ->where('following_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return response()->json([
                'message' => 'Follow request already sent',
            ], 400);
        }

        FollowRequest::create([
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Follow request sent',
        ]);
    }

    public function index(Request $request)
    {
        $requests = FollowRequest::where('following_id', $request->user()->id)
            ->where('status', 'pending')
            ->with('follower')
            ->latest()
            ->paginate(config('pagination.default'));

        return response()->json($requests);
    }

    public function accept(Request $request, FollowRequest $followRequest)
    {
        if ($followRequest->following_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $followRequest->update(['status' => 'accepted']);

        $request->user()->followers()->attach($followRequest->follower_id);

        return response()->json([
            'message' => 'Follow request accepted',
        ]);
    }

    public function reject(Request $request, FollowRequest $followRequest)
    {
        if ($followRequest->following_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $followRequest->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Follow request rejected',
        ]);
    }
}
