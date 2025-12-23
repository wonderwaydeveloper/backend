<?php

namespace App\Repositories\Cache;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\DTOs\UserRegistrationDTO;
use App\DTOs\UserUpdateDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CachedUserRepository implements UserRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private UserRepositoryInterface $repository
    ) {}

    public function find(int $id): ?User
    {
        return Cache::remember(
            "user.{$id}",
            self::CACHE_TTL,
            fn() => $this->repository->find($id)
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

    public function create(UserRegistrationDTO $dto): User
    {
        $user = $this->repository->create($dto);
        $this->clearUserCache($user->id);
        return $user;
    }

    public function update(int $id, UserUpdateDTO $dto): User
    {
        $user = $this->repository->update($id, $dto);
        $this->clearUserCache($id);
        return $user;
    }

    public function delete(int $id): bool
    {
        $result = $this->repository->delete($id);
        if ($result) {
            $this->clearUserCache($id);
        }
        return $result;
    }

    public function getFollowers(int $userId): LengthAwarePaginator
    {
        return $this->repository->getFollowers($userId);
    }

    public function getFollowing(int $userId): LengthAwarePaginator
    {
        return $this->repository->getFollowing($userId);
    }

    public function search(string $query): LengthAwarePaginator
    {
        return $this->repository->search($query);
    }

    public function getSuggestions(int $userId, int $limit = 10): Collection
    {
        return Cache::remember(
            "user.suggestions.{$userId}.{$limit}",
            1800, // 30 minutes
            fn() => $this->repository->getSuggestions($userId, $limit)
        );
    }

    private function clearUserCache(int $userId): void
    {
        Cache::forget("user.{$userId}");
        Cache::forget("user.suggestions.{$userId}.*");
    }
}