<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenericResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;


class UserController extends Controller
{

    use AuthorizesRequests;

    public function __construct(private UserService $userService)
    {
    }

    /**
     * نمایش تمام کاربران
     */
    public function index(Request $request)
    {
        try {
            $this->authorize('manageUsers', User::class);

            $users = $this->userService->getUsers($request->all());

            return GenericResource::success(
                UserResource::collection($users),
                'Users retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * نمایش پروفایل کاربر
     */
    public function show(Request $request, User $user)
    {
        try {
            $this->authorize('view', $user);

            $user->load('followers', 'following');

            return GenericResource::success(
                new UserResource($user),
                'User profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 403);
        }
    }

    /**
     * آپدیت پروفایل کاربر
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|alpha_dash|max:255|unique:users,username,' . $user->id,
            'bio' => 'sometimes|string|max:500',
            'website' => 'sometimes|url|max:255',
            'location' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|image|max:2048',
            'cover_image' => 'sometimes|image|max:5120',
            'is_private' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $this->authorize('update', $user);

            $user = $this->userService->updateProfile($user, $request->all());

            return GenericResource::success(
                new UserResource($user->load('followers', 'following')),
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دنبال کردن کاربر
     */
    public function follow(Request $request, User $user)
    {
        try {
            $this->authorize('follow', $user);

            $result = $this->userService->followUser($request->user(), $user);

            return GenericResource::success([
                'following' => $result['following'],
                'requires_approval' => $result['requires_approval'],
                'followers_count' => $user->fresh()->followers_count,
            ], $result['message']);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * آنفالو کاربر
     */
    public function unfollow(Request $request, User $user)
    {
        try {
            $unfollowed = $this->userService->unfollowUser($request->user(), $user);

            return GenericResource::success([
                'unfollowed' => $unfollowed,
                'followers_count' => $user->fresh()->followers_count,
            ], 'User unfollowed successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت دنبال‌کنندگان
     */
    public function followers(Request $request, User $user)
    {
        try {
            $this->authorize('view', $user);

            $followers = $this->userService->getFollowers($user, $request->all());

            return GenericResource::success(
                UserResource::collection($followers),
                'Followers retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت کاربران دنبال شونده
     */
    public function following(Request $request, User $user)
    {
        try {
            $this->authorize('view', $user);

            $following = $this->userService->getFollowing($user, $request->all());

            return GenericResource::success(
                UserResource::collection($following),
                'Following retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * دریافت درخواست‌های دنبال کردن
     */
    public function followRequests(Request $request)
    {
        try {
            $this->authorize('manageFollowRequests', $request->user());

            $requests = $this->userService->getFollowRequests($request->user());

            return GenericResource::success(
                UserResource::collection($requests),
                'Follow requests retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * قبول درخواست دنبال کردن
     */
    public function acceptFollowRequest(Request $request, User $follower)
    {
        try {
            $this->authorize('manageFollowRequests', $request->user());

            $accepted = $this->userService->acceptFollowRequest($request->user(), $follower);

            return GenericResource::success([
                'accepted' => $accepted,
            ], 'Follow request accepted successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * رد درخواست دنبال کردن
     */
    public function rejectFollowRequest(Request $request, User $follower)
    {
        try {
            $this->authorize('manageFollowRequests', $request->user());

            $rejected = $this->userService->rejectFollowRequest($request->user(), $follower);

            return GenericResource::success([
                'rejected' => $rejected,
            ], 'Follow request rejected successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * مسدود کردن کاربر
     */
    public function ban(Request $request, User $user)
    {
        try {
            $this->authorize('ban', $user);

            $banned = $this->userService->banUser($user);

            return GenericResource::success([
                'banned' => $banned,
            ], 'User banned successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * آزاد کردن کاربر
     */
    public function unban(Request $request, User $user)
    {
        try {
            $this->authorize('ban', $user);

            $unbanned = $this->userService->unbanUser($user);

            return GenericResource::success([
                'unbanned' => $unbanned,
            ], 'User unbanned successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * جستجوی کاربران
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $users = $this->userService->searchUsers($request->query, $request->user());

            return GenericResource::success(
                UserResource::collection($users),
                'Users search completed successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }


    /**
     * نمایش پروفایل کاربر جاری
     */
    public function showCurrent(Request $request)
    {
        try {
            $user = $request->user()->load('followers', 'following');

            return GenericResource::success(
                new UserResource($user),
                'Current user profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * آپدیت پروفایل کاربر جاری
     */
    public function updateCurrent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'username' => 'sometimes|string|alpha_dash|max:255|unique:users,username,' . $request->user()->id,
            'bio' => 'sometimes|string|max:500',
            'website' => 'sometimes|url|max:255',
            'location' => 'sometimes|string|max:255',
            'avatar' => 'sometimes|image|max:2048',
            'cover_image' => 'sometimes|image|max:5120',
            'is_private' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $user = $this->userService->updateProfile($request->user(), $request->all());

            return GenericResource::success(
                new UserResource($user->load('followers', 'following')),
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

}