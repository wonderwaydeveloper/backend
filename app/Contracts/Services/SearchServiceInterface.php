<?php

namespace App\Contracts\Services;

use App\DTOs\SearchDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SearchServiceInterface
{
    public function searchPosts(SearchDTO $dto): LengthAwarePaginator;
    
    public function searchUsers(SearchDTO $dto): LengthAwarePaginator;
    
    public function searchHashtags(SearchDTO $dto): LengthAwarePaginator;
    
    public function searchAll(SearchDTO $dto): array;
    
    public function getSuggestions(string $query): Collection;
    
    public function getRecentSearches(int $userId): Collection;
    
    public function saveSearch(int $userId, string $query): bool;
    
    public function clearSearchHistory(int $userId): bool;
}