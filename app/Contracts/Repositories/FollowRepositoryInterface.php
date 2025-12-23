<?php

namespace App\Contracts\Repositories;

use App\DTOs\FollowDTO;
use App\Models\Follow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface FollowRepositoryInterface
{
    public function find(int $id): ?Follow;
    
    public function create(FollowDTO $dto): Follow;
    
    public function delete(int $followerId, int $followingId): bool;
    
    public function isFollowing(int $followerId, int $followingId): bool;
    
    public function getFollowers(int $userId): LengthAwarePaginator;
    
    public function getFollowing(int $userId): LengthAwarePaginator;
    
    public function getFollowersCount(int $userId): int;
    
    public function getFollowingCount(int $userId): int;
    
    public function getMutualFollows(int $userId1, int $userId2): Collection;
    
    public function getSuggestions(int $userId, int $limit = 10): Collection;
}