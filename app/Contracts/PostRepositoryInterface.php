<?php

namespace App\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    public function find(int $id): ?Post;
    
    public function create(array $data): Post;
    
    public function update(Post $post, array $data): bool;
    
    public function delete(Post $post): bool;
    
    public function getPublicPosts(int $limit = 20): LengthAwarePaginator;
    
    public function getUserPosts(int $userId, int $limit = 20): LengthAwarePaginator;
    
    public function getTimeline(int $userId, int $limit = 20): array;
    
    public function searchPosts(string $query, array $filters = []): array;
    
    public function getTrendingPosts(int $limit = 20): array;
}