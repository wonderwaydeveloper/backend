<?php

namespace App\Http\Controllers\Api;

use App\Actions\User\{UpdateUserProfileAction, FollowUserAction, UnfollowUserAction};
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
        private UserService $userService,
        private UpdateUserProfileAction $updateAction,
        private FollowUserAction $followAction,
        private UnfollowUserAction $unfollowAction
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
        $user = $this->updateAction->execute($request->user(), $dto);
        return response()->json(new UserResource($user));
    }

    public function follow(User $user): JsonResponse
    {
        $result = $this->followAction->execute(auth()->user(), $user);
        return response()->json($result);
    }

    public function unfollow(User $user): JsonResponse
    {
        $result = $this->unfollowAction->execute(auth()->user(), $user);
        return response()->json($result);
    }

    public function updatePrivacy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->only(['is_private']));
        return response()->json(new UserResource($user));
    }
}
