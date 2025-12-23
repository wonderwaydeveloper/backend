<?php

namespace App\Contracts\Repositories;

use App\DTOs\HashtagDTO;
use App\Models\Hashtag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface HashtagRepositoryInterface
{
    public function find(int $id): ?Hashtag;
    
    public function findByName(string $name): ?Hashtag;
    
    public function create(HashtagDTO $dto): Hashtag;
    
    public function update(int $id, HashtagDTO $dto): Hashtag;
    
    public function delete(int $id): bool;
    
    public function getTrending(int $limit = 10): Collection;
    
    public function search(string $query): LengthAwarePaginator;
    
    public function getHashtagPosts(int $hashtagId): LengthAwarePaginator;
    
    public function incrementUsage(int $hashtagId): bool;
    
    public function getPopular(int $limit = 20): Collection;
}