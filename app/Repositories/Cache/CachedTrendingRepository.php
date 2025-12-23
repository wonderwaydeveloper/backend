<?php

namespace App\Repositories\Cache;

use App\Contracts\Repositories\HashtagRepositoryInterface;
use App\DTOs\HashtagDTO;
use App\Models\Hashtag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class CachedTrendingRepository implements HashtagRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private HashtagRepositoryInterface $repository
    ) {}

    public function find(int $id): ?Hashtag
    {
        return Cache::remember(
            "hashtag.{$id}",
            self::CACHE_TTL,
            fn() => $this->repository->find($id)
        );
    }

    public function findByName(string $name): ?Hashtag
    {
        return Cache::remember(
            "hashtag.name.{$name}",
            self::CACHE_TTL,
            fn() => $this->repository->findByName($name)
        );
    }

    public function create(HashtagDTO $dto): Hashtag
    {
        $hashtag = $this->repository->create($dto);
        $this->clearTrendingCache();
        return $hashtag;
    }

    public function update(int $id, HashtagDTO $dto): Hashtag
    {
        $hashtag = $this->repository->update($id, $dto);
        $this->clearHashtagCache($id);
        return $hashtag;
    }

    public function delete(int $id): bool
    {
        $result = $this->repository->delete($id);
        if ($result) {
            $this->clearHashtagCache($id);
        }
        return $result;
    }

    public function getTrending(int $limit = 10): Collection
    {
        return Cache::remember(
            "hashtags.trending.{$limit}",
            1800, // 30 minutes
            fn() => $this->repository->getTrending($limit)
        );
    }

    public function search(string $query): LengthAwarePaginator
    {
        return $this->repository->search($query);
    }

    public function getHashtagPosts(int $hashtagId): LengthAwarePaginator
    {
        return $this->repository->getHashtagPosts($hashtagId);
    }

    public function incrementUsage(int $hashtagId): bool
    {
        $result = $this->repository->incrementUsage($hashtagId);
        if ($result) {
            $this->clearHashtagCache($hashtagId);
        }
        return $result;
    }

    public function getPopular(int $limit = 20): Collection
    {
        return Cache::remember(
            "hashtags.popular.{$limit}",
            self::CACHE_TTL,
            fn() => $this->repository->getPopular($limit)
        );
    }

    private function clearHashtagCache(int $hashtagId): void
    {
        Cache::forget("hashtag.{$hashtagId}");
        $this->clearTrendingCache();
    }

    private function clearTrendingCache(): void
    {
        Cache::forget('hashtags.trending.*');
        Cache::forget('hashtags.popular.*');
    }
}