<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSuggestionService
{
    public function getSuggestions($userId, $limit = 10)
    {
        $followingIds = User::find($userId)->following()->pluck('users.id')->toArray();
        $followingIds[] = $userId;

        // Users with mutual followers
        $suggestions = User::select('users.*', DB::raw('COUNT(DISTINCT follows.follower_id) as mutual_count'))
            ->join('follows', 'users.id', '=', 'follows.following_id')
            ->whereIn('follows.follower_id', $followingIds)
            ->whereNotIn('users.id', $followingIds)
            ->groupBy('users.id')
            ->orderByDesc('mutual_count')
            ->limit($limit)
            ->get();

        if ($suggestions->count() < $limit) {
            $remaining = $limit - $suggestions->count();
            $excludeIds = array_merge($followingIds, $suggestions->pluck('id')->toArray());

            $popularUsers = User::whereNotIn('id', $excludeIds)
                ->withCount('followers')
                ->orderByDesc('followers_count')
                ->limit($remaining)
                ->get();

            $suggestions = $suggestions->merge($popularUsers);
        }

        return $suggestions;
    }
}
