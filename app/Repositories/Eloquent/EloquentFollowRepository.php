<?php

namespace App\Repositories\Eloquent;

use App\Contracts\Repositories\FollowRepositoryInterface;
use App\DTOs\FollowDTO;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentFollowRepository implements FollowRepositoryInterface
{
    public function find(int $id): ?Follow
    {
        return Follow::find($id);
    }

    public function create(FollowDTO $dto): Follow
    {
        return Follow::create($dto->toArray());
    }

    public function delete(int $followerId, int $followingId): bool
    {
        return Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->delete() > 0;
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        return Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->exists();
    }

    public function getFollowers(int $userId): LengthAwarePaginator
    {
        return User::whereHas('following', function ($query) use ($userId) {
            $query->where('following_id', $userId);
        })
        ->select('id', 'name', 'username', 'avatar', 'bio')
        ->paginate(20);
    }

    public function getFollowing(int $userId): LengthAwarePaginator
    {
        return User::whereHas('followers', function ($query) use ($userId) {
            $query->where('follower_id', $userId);
        })
        ->select('id', 'name', 'username', 'avatar', 'bio')
        ->paginate(20);
    }

    public function getFollowersCount(int $userId): int
    {
        return Follow::where('following_id', $userId)->count();
    }

    public function getFollowingCount(int $userId): int
    {
        return Follow::where('follower_id', $userId)->count();
    }

    public function getMutualFollows(int $userId1, int $userId2): Collection
    {
        $user1Following = Follow::where('follower_id', $userId1)
            ->pluck('following_id');
        
        $user2Following = Follow::where('follower_id', $userId2)
            ->pluck('following_id');

        $mutualIds = $user1Following->intersect($user2Following);

        return User::whereIn('id', $mutualIds)
            ->select('id', 'name', 'username', 'avatar')
            ->get();
    }

    public function getSuggestions(int $userId, int $limit = 10): Collection
    {
        // Get users followed by people the current user follows
        $suggestions = User::whereHas('followers', function ($query) use ($userId) {
            $query->whereIn('follower_id', function ($subQuery) use ($userId) {
                $subQuery->select('following_id')
                    ->from('follows')
                    ->where('follower_id', $userId);
            });
        })
        ->where('id', '!=', $userId)
        ->whereNotIn('id', function ($query) use ($userId) {
            $query->select('following_id')
                ->from('follows')
                ->where('follower_id', $userId);
        })
        ->withCount('followers')
        ->orderBy('followers_count', 'desc')
        ->limit($limit)
        ->get();

        return $suggestions;
    }
}