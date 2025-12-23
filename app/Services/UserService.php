<?php

namespace App\Services;

use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use App\Services\{UserFollowService, UserModerationService};
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserFollowService $followService,
        private UserModerationService $moderationService
    ) {
    }

    public function getUserProfile(User $user): User
    {
        return $this->userRepository->getUserWithCounts($user->id);
    }

    public function getUserPosts(User $user): LengthAwarePaginator
    {
        return $this->userRepository->getUserPosts($user->id);
    }

    public function updateProfile(int $userId, \App\DTOs\UserUpdateDTO $dto): User
    {
        $user = User::findOrFail($userId);
        return $this->userRepository->update($user, $dto->toArray());
    }

    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return $this->userRepository->searchUsers($query, $limit);
    }

    public function updatePrivacySettings(int $userId, array $settings): User
    {
        $user = User::findOrFail($userId);
        return $this->userRepository->update($user, $settings);
    }

    public function register(\App\DTOs\UserRegistrationDTO $dto): User
    {
        $userData = $dto->toArray();
        $userData['password'] = bcrypt($userData['password']);
        return $this->userRepository->create($userData);
    }

    public function getUserById(int $userId): User
    {
        return $this->userRepository->findById($userId) ?? throw new \App\Exceptions\UserNotFoundException();
    }

    public function getSuggestions(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->userRepository->getSuggestedUsers($userId, 10);
    }

    public function deactivateAccount(int $userId): bool
    {
        $user = $this->getUserById($userId);
        return $this->userRepository->update($user, ['is_active' => false]) !== null;
    }

    public function search(string $query): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->userRepository->searchUsers($query, 20);
    }

    // Delegate to specialized services
    public function follow(int $userId, int $targetUserId): bool
    {
        return $this->followService->follow($userId, $targetUserId);
    }

    public function unfollow(int $userId, int $targetUserId): bool
    {
        return $this->followService->unfollow($userId, $targetUserId);
    }

    public function getFollowers(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->followService->getFollowers($userId);
    }

    public function getFollowing(int $userId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->followService->getFollowing($userId);
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