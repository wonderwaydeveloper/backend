<?php

namespace App\Repositories\Cache;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CachedUserRepository implements UserRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private UserRepositoryInterface $repository
    ) {}

    public function create(array $data): User
    {
        $user = $this->repository->create($data);
        $this->clearUserCache($user->id);
        return $user;
    }

    public function findById(int $id): ?User
    {
        return Cache::remember(
            "user.{$id}",
            self::CACHE_TTL,
            fn() => $this->repository->findById($id)
        );
    }

    public function findByEmail(string $email): ?User
    {
        return Cache::remember(
            "user.email.{$email}",
            self::CACHE_TTL,
            fn() => $this->repository->findByEmail($email)
        );
    }

    public function findByUsername(string $username): ?User
    {
        return Cache::remember(
            "user.username.{$username}",
            self::CACHE_TTL,
            fn() => $this->repository->findByUsername($username)
        );
    }

    public function update(User $user, array $data): User
    {
        $result = $this->repository->update($user, $data);
        $this->clearUserCache($user->id);
        return $result;
    }

    public function delete(User $user): bool
    {
        $result = $this->repository->delete($user);
        if ($result) {
            $this->clearUserCache($user->id);
        }
        return $result;
    }

    public function getUserWithCounts(int $id): ?User
    {
        return Cache::remember(
            "user.counts.{$id}",
            self::CACHE_TTL,
            fn() => $this->repository->getUserWithCounts($id)
        );
    }

    public function getUserPosts(int $userId): LengthAwarePaginator
    {
        return $this->repository->getUserPosts($userId);
    }

    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return $this->repository->searchUsers($query, $limit);
    }

    public function getFollowers(int $userId, int $limit = 20): Collection
    {
        return $this->repository->getFollowers($userId, $limit);
    }

    public function getFollowing(int $userId, int $limit = 20): Collection
    {
        return $this->repository->getFollowing($userId, $limit);
    }

    public function getSuggestedUsers(int $userId, int $limit = 10): Collection
    {
        return Cache::remember(
            "user.suggestions.{$userId}.{$limit}",
            1800, // 30 minutes
            fn() => $this->repository->getSuggestedUsers($userId, $limit)
        );
    }

    public function getMentionableUsers(string $query, int $limit = 10): Collection
    {
        return $this->repository->getMentionableUsers($query, $limit);
    }

    private function clearUserCache(int $userId): void
    {
        Cache::forget("user.{$userId}");
        Cache::forget("user.counts.{$userId}");
        Cache::forget("user.suggestions.{$userId}.*");
    }
}