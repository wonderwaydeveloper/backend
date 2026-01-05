<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\{JsonResponse, Request};

class ProfileController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function show(User $user): JsonResponse
    {
        return response()->json(new UserResource($user->load(['followers', 'following'])));
    }

    public function posts(User $user): JsonResponse
    {
        $posts = $this->userService->getUserPosts($user);
        return response()->json($posts);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $dto = UserUpdateDTO::fromRequest($request);
        $user = $this->userService->updateUserProfile($request->user(), $dto);
        return response()->json(new UserResource($user));
    }

    public function follow(User $user): JsonResponse
    {
        $result = $this->userService->followUser(auth()->user(), $user);
        return response()->json($result);
    }

    public function unfollow(User $user): JsonResponse
    {
        $result = $this->userService->unfollowUser(auth()->user(), $user);
        return response()->json($result);
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        $request->validate([
            'is_private' => 'required|boolean',
            'email_notifications_enabled' => 'sometimes|boolean'
        ]);
        
        $user = $request->user();
        $user->update($request->only(['is_private', 'email_notifications_enabled']));
        return response()->json(new UserResource($user));
    }

    public function updateVerification(Request $request, User $user): JsonResponse
    {
        // Only admins can verify users
        if (!$request->user()->hasRole('admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $request->validate(['verified' => 'required|boolean']);
        $user->update(['verified' => $request->verified]);
        
        return response()->json(new UserResource($user));
    }
}
