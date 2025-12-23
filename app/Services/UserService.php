<?php

namespace App\Services;

use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get user profile with counts
     */
    public function getUserProfile(User $user): User
    {
        return $this->userRepository->getUserWithCounts($user->id);
    }

    /**
     * Get user posts with pagination
     */
    public function getUserPosts(User $user): LengthAwarePaginator
    {
        return $this->userRepository->getUserPosts($user->id);
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, \App\DTOs\UserUpdateDTO $dto): User
    {
        $user = User::findOrFail($userId);
        $updateData = $dto->toArray();
        
        return $this->userRepository->update($user, $updateData);
    }

    /**
     * Search users by name or username
     */
    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return $this->userRepository->searchUsers($query, $limit);
    }

    /**
     * Update user privacy settings
     */
    public function updatePrivacySettings(int $userId, array $settings): User
    {
        $user = User::findOrFail($userId);
        $user->update($settings);
        return $user;
    }

    /**
     * Block user
     */
    public function blockUser(User $user, User $targetUser): array
    {
        // Add block logic here
        // For now, just return success message
        return [
            'message' => 'User blocked successfully',
            'blocked_user' => $targetUser->only(['id', 'name', 'username']),
        ];
    }

    /**
     * Unblock user
     */
    public function unblockUser(User $user, User $targetUser): array
    {
        // Add unblock logic here
        // For now, just return success message
        return [
            'message' => 'User unblocked successfully',
            'unblocked_user' => $targetUser->only(['id', 'name', 'username']),
        ];
    }

    /**
     * Mute user
     */
    public function muteUser(User $user, User $targetUser): array
    {
        // Add mute logic here
        // For now, just return success message
        return [
            'message' => 'User muted successfully',
            'muted_user' => $targetUser->only(['id', 'name', 'username']),
        ];
    }

    /**
     * Unmute user
     */
    public function unmuteUser(User $user, User $targetUser): array
    {
        // Add unmute logic here
        // For now, just return success message
        return [
            'message' => 'User unmuted successfully',
            'unmuted_user' => $targetUser->only(['id', 'name', 'username']),
        ];
    }

    /**
     * Register new user
     */
    public function register(\App\DTOs\UserRegistrationDTO $dto): User
    {
        $userData = $dto->toArray();
        $userData['password'] = bcrypt($userData['password']);
        return User::create($userData);
    }

    /**
     * Follow user
     */
    public function follow(int $userId, int $targetUserId): bool
    {
        $user = User::findOrFail($userId);
        $targetUser = User::findOrFail($targetUserId);
        
        if (!$user->following()->where('following_id', $targetUserId)->exists()) {
            $user->following()->attach($targetUserId);
            return true;
        }
        return false;
    }

    /**
     * Unfollow user
     */
    public function unfollow(int $userId, int $targetUserId): bool
    {
        $user = User::findOrFail($userId);
        return $user->following()->detach($targetUserId) > 0;
    }

    /**
     * Get user followers
     */
    public function getFollowers(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = User::findOrFail($userId);
        return $user->followers()->paginate(20);
    }

    /**
     * Get user following
     */
    public function getFollowing(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = User::findOrFail($userId);
        return $user->following()->paginate(20);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): User
    {
        return User::findOrFail($userId);
    }

    /**
     * Get user suggestions
     */
    public function getSuggestions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        $user = User::findOrFail($userId);
        return User::whereNotIn('id', $user->following()->pluck('following_id'))
            ->where('id', '!=', $userId)
            ->limit(10)
            ->get();
    }

    /**
     * Deactivate user account
     */
    public function deactivateAccount(int $userId): bool
    {
        $user = User::findOrFail($userId);
        return $user->update(['is_active' => false]);
    }

    /**
     * Search users
     */
    public function search(string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->paginate(20);
    }
}
