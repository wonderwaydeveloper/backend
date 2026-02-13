<?php

namespace App\Services;

use App\Contracts\Services\UserServiceInterface;
use App\Models\User;
use App\Services\{UserFollowService, UserModerationService};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserFollowService $followService,
        private UserModerationService $moderationService
    ) {
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function getUserWithCounts(int $id): ?User
    {
        return User::withCount(['posts', 'followers', 'following'])->find($id);
    }

    public function getUserPosts(User $user): LengthAwarePaginator
    {
        return User::findOrFail($user->id)->posts()->paginate(20);
    }

    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->limit($limit)
            ->get();
    }

    public function getFollowers(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::findOrFail($userId)->followers()->paginate(20);
    }

    public function getFollowing(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::findOrFail($userId)->following()->paginate(20);
    }

    public function getSuggestedUsers(int $userId, int $limit = 10): Collection
    {
        return User::where('id', '!=', $userId)
            ->whereNotIn('id', function ($query) use ($userId) {
                $query->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $userId);
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getMentionableUsers(string $query, int $limit = 10): Collection
    {
        return User::where('username', 'like', "%{$query}%")
            ->limit($limit)
            ->get();
    }

    public function getUserProfile(User $user): User
    {
        return $this->getUserWithCounts($user->id);
    }

    public function updateProfile(int $userId, \App\DTOs\UserUpdateDTO $dto): User
    {
        $user = User::findOrFail($userId);
        return $this->update($user, $dto->toArray());
    }

    public function updateUserProfile(User $user, \App\DTOs\UserUpdateDTO $dto): User
    {
        return $this->update($user, $dto->toArray());
    }

    public function followUser(User $user, User $targetUser): array
    {
        $this->follow($user->id, $targetUser->id);
        return ['message' => 'User followed successfully'];
    }

    public function unfollowUser(User $user, User $targetUser): array
    {
        $this->unfollow($user->id, $targetUser->id);
        return ['message' => 'User unfollowed successfully'];
    }

    public function updatePrivacySettings(int $userId, array $settings): User
    {
        $user = User::findOrFail($userId);
        return $this->update($user, $settings);
    }

    public function register(\App\DTOs\UserRegistrationDTO $dto): User
    {
        $userData = $dto->toArray();
        $userData['password'] = bcrypt($userData['password']);
        return $this->create($userData);
    }

    public function getUserById(int $userId): User
    {
        return $this->findById($userId) ?? throw new \App\Exceptions\UserNotFoundException();
    }

    public function getSuggestions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getSuggestedUsers($userId, 10);
    }

    public function deactivateAccount(int $userId): bool
    {
        $user = $this->getUserById($userId);
        return $this->update($user, ['is_active' => false]) !== null;
    }

    public function search(string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::where('name', 'like', "%{$query}%")
            ->orWhere('username', 'like', "%{$query}%")
            ->paginate(20);
    }

    // Delegate to specialized services
    public function follow(int $userId, int $targetUserId): bool
    {
        // Counter updates are now handled inside transaction in UserFollowService
        return $this->followService->follow($userId, $targetUserId);
    }

    public function unfollow(int $userId, int $targetUserId): bool
    {
        // Counter updates are now handled inside transaction in UserFollowService
        return $this->followService->unfollow($userId, $targetUserId);
    }

    public function blockUser(User $user, User $targetUser): array
    {
        return $this->moderationService->blockUser($user, $targetUser);
    }

    public function unblockUser(User $user, User $targetUser): array
    {
        return $this->moderationService->unblockUser($user, $targetUser);
    }

    public function muteUser(User $user, User $targetUser): array
    {
        return $this->moderationService->muteUser($user, $targetUser);
    }

    public function unmuteUser(User $user, User $targetUser): array
    {
        return $this->moderationService->unmuteUser($user, $targetUser);
    }
}