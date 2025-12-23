<?php

namespace App\Contracts\Repositories;

use App\Models\Like;
use Illuminate\Database\Eloquent\Collection;

interface LikeRepositoryInterface
{
    public function find(int $id): ?Like;
    
    public function create(int $userId, int $postId): Like;
    
    public function delete(int $userId, int $postId): bool;
    
    public function isLiked(int $userId, int $postId): bool;
    
    public function getPostLikes(int $postId): Collection;
    
    public function getUserLikes(int $userId): Collection;
    
    public function getLikesCount(int $postId): int;
    
    public function getRecentLikes(int $postId, int $limit = 10): Collection;
}