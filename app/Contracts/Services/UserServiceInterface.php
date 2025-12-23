<?php

namespace App\Contracts\Services;

use App\DTOs\UserDTO;
use App\DTOs\UserRegistrationDTO;
use App\DTOs\UserUpdateDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserServiceInterface
{
    public function register(UserRegistrationDTO $dto): User;
    
    public function updateProfile(int $userId, UserUpdateDTO $dto): User;
    
    public function follow(int $followerId, int $followingId): bool;
    
    public function unfollow(int $followerId, int $followingId): bool;
    
    public function getFollowers(int $userId): LengthAwarePaginator;
    
    public function getFollowing(int $userId): LengthAwarePaginator;
    
    public function getSuggestions(int $userId): Collection;
    
    public function search(string $query): LengthAwarePaginator;
    
    public function updatePrivacySettings(int $userId, array $settings): User;
    
    public function deactivateAccount(int $userId): bool;
}