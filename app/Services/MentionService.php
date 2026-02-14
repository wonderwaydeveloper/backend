<?php

namespace App\Services;

use App\Models\{Mention, User};
use Illuminate\Support\Collection;

class MentionService
{
    public function searchUsers(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect([]);
        }

        $currentUser = auth()->user();
        $blockedIds = $currentUser->blockedUsers()->pluck('users.id')->toArray();
        $blockedByIds = $currentUser->blockedBy()->pluck('users.id')->toArray();
        $excludedIds = array_merge($blockedIds, $blockedByIds);

        return User::where(function($q) use ($query) {
                $q->where('username', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->whereNotIn('id', $excludedIds)
            ->where('id', '!=', $currentUser->id)
            ->select('id', 'username', 'name', 'avatar')
            ->limit($limit)
            ->get();
    }

    public function getUserMentions(User $user, int $perPage = 20)
    {
        return $user->mentions()
            ->with(['mentionable' => function ($morphTo) {
                $morphTo->morphWith([
                    'App\Models\Post' => ['user:id,username,name,avatar'],
                    'App\Models\Comment' => ['user:id,username,name,avatar', 'post:id,content'],
                ]);
            }])
            ->latest()
            ->paginate($perPage);
    }

    public function getMentionsForContent(string $type, int $id): Collection
    {
        $model = $type === 'post' ? 'App\Models\Post' : 'App\Models\Comment';

        return Mention::where('mentionable_type', $model)
            ->where('mentionable_id', $id)
            ->with('user:id,username,name,avatar')
            ->get();
    }
}
