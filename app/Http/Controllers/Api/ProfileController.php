<?php

namespace App\Http\Controllers\Api;

use App\DTOs\UserUpdateDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Block;
use App\Models\Mute;
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

    public function media(User $user): JsonResponse
    {
        $mediaPosts = $user->posts()
            ->whereHas('media')
            ->with(['media', 'user:id,name,username,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        return response()->json($mediaPosts);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        
        // Handle file uploads
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = asset('storage/' . $avatarPath);
        }
        
        if ($request->hasFile('cover')) {
            $coverPath = $request->file('cover')->store('covers', 'public');
            $data['cover'] = asset('storage/' . $coverPath);
        }
        
        $dto = UserUpdateDTO::fromArray($data);
        $updatedUser = $this->userService->updateUserProfile($user, $dto);
        
        return response()->json(new UserResource($updatedUser));
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
    
    public function getPrivacySettings(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'is_private' => $user->is_private,
            'email_notifications_enabled' => $user->email_notifications_enabled,
            'two_factor_enabled' => $user->two_factor_enabled,
            'notification_preferences' => $user->notification_preferences ?? []
        ]);
    }
    
    public function updatePrivacySettings(Request $request): JsonResponse
    {
        $request->validate([
            'is_private' => 'sometimes|boolean',
            'email_notifications_enabled' => 'sometimes|boolean',
            'notification_preferences' => 'sometimes|array'
        ]);
        
        $user = $request->user();
        $user->update($request->only([
            'is_private', 
            'email_notifications_enabled',
            'notification_preferences'
        ]));
        
        return response()->json([
            'message' => 'Privacy settings updated successfully',
            'settings' => [
                'is_private' => $user->is_private,
                'email_notifications_enabled' => $user->email_notifications_enabled,
                'notification_preferences' => $user->notification_preferences ?? []
            ]
        ]);
    }
    
    public function block(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        
        // Prevent self-blocking
        if ($currentUser->id === $user->id) {
            return response()->json(['error' => 'Cannot block yourself'], 422);
        }
        
        $request->validate(['reason' => 'nullable|string|max:255']);
        
        Block::firstOrCreate(
            ['blocker_id' => $currentUser->id, 'blocked_id' => $user->id],
            ['reason' => $request->input('reason')]
        );
        
        // Auto-unfollow when blocking
        $currentUser->following()->detach($user->id);
        $user->following()->detach($currentUser->id);
        
        return response()->json(['message' => 'User blocked successfully']);
    }
    
    public function unblock(Request $request, User $user): JsonResponse
    {
        $deleted = Block::where('blocker_id', $request->user()->id)
            ->where('blocked_id', $user->id)
            ->delete();
        
        if (!$deleted) {
            return response()->json(['error' => 'User is not blocked'], 404);
        }
        
        return response()->json(['message' => 'User unblocked successfully']);
    }
    
    public function mute(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        
        // Prevent self-muting
        if ($currentUser->id === $user->id) {
            return response()->json(['error' => 'Cannot mute yourself'], 422);
        }
        
        $request->validate(['expires_at' => 'nullable|date|after:now|before:+1 year']);
        
        Mute::firstOrCreate(
            ['muter_id' => $currentUser->id, 'muted_id' => $user->id],
            ['expires_at' => $request->input('expires_at')]
        );
        
        return response()->json(['message' => 'User muted successfully']);
    }
    
    public function unmute(Request $request, User $user): JsonResponse
    {
        $deleted = Mute::where('muter_id', $request->user()->id)
            ->where('muted_id', $user->id)
            ->delete();
        
        if (!$deleted) {
            return response()->json(['error' => 'User is not muted'], 404);
        }
        
        return response()->json(['message' => 'User unmuted successfully']);
    }
    
    public function getBlockedUsers(Request $request): JsonResponse
    {
        $blockedUsers = $request->user()
            ->blockedUsers()
            ->select(['users.id', 'users.username', 'users.name', 'users.avatar'])
            ->withPivot('reason', 'created_at')
            ->paginate(50);
        
        return response()->json($blockedUsers);
    }
    
    public function getMutedUsers(Request $request): JsonResponse
    {
        $mutedUsers = $request->user()
            ->mutedUsers()
            ->select(['users.id', 'users.username', 'users.name', 'users.avatar'])
            ->withPivot('expires_at', 'created_at')
            ->paginate(50);
        
        return response()->json($mutedUsers);
    }
    
    public function exportData(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Collect user data
        $userData = [
            'profile' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'bio' => $user->bio,
                'location' => $user->location,
                'website' => $user->website,
                'created_at' => $user->created_at,
            ],
            'posts' => $user->posts()->select(['id', 'content', 'created_at'])->get(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'bookmarks' => $user->bookmarks()->with('post:id,content')->get(),
        ];
        
        return response()->json([
            'message' => 'Data export prepared',
            'data' => $userData,
            'export_date' => now()->toISOString()
        ]);
    }
    
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|string|in:DELETE_MY_ACCOUNT'
        ]);
        
        $user = $request->user();
        
        if (!\Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid password'], 422);
        }
        
        // Delete user data
        $user->tokens()->delete();
        $user->devices()->delete();
        $user->posts()->delete();
        $user->delete();
        
        return response()->json(['message' => 'Account deleted successfully']);
    }
}