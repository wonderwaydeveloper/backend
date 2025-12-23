<?php

namespace App\Contracts\Services;

use Illuminate\Database\Eloquent\Collection;

interface TrendingServiceInterface
{
    public function getTrendingHashtags(int $limit = 10): Collection;
    
    public function getTrendingPosts(int $limit = 20): Collection;
    
    public function getTrendingUsers(int $limit = 10): Collection;
    
    public function getPersonalizedTrending(int $userId, int $limit = 20): Collection;
    
    public function getTrendVelocity(string $hashtag): float;
    
    public function refreshTrendingData(): bool;
    
    public function getTrendingStats(): array;
    
    public function getTrendingByLocation(string $location): Collection;
}