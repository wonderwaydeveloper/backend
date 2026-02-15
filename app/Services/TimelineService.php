<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TimelineService
{
    public function getLiveTimeline(User $user, int $perPage = 20): array
    {
        $followingIds = Cache::tags(["user:{$user->id}"])->remember(
            "following:{$user->id}",
            3600,
            fn () => $user->following()->pluck('users.id')->toArray()
        );

        $followingIds[] = $user->id;

        $posts = Post::select([
            'id', 'user_id', 'content', 'image', 'gif_url', 'created_at',
            'likes_count', 'comments_count', 'reposts_count', 'views_count'
        ])
            ->with(['user:id,name,username,avatar,verified,verification_type'])
            ->whereIn('user_id', $followingIds)
            ->where('created_at', '>', now()->subHours(2))
            ->where('is_draft', false)
            ->latest('created_at')
            ->paginate($perPage);

        $postIds = $posts->pluck('id')->toArray();
        $userLikes = [];

        if (!empty($postIds)) {
            $userLikes = DB::table('likes')
                ->where('user_id', $user->id)
                ->whereIn('likeable_id', $postIds)
                ->where('likeable_type', Post::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        $posts->getCollection()->transform(function ($post) use ($userLikes) {
            $post->is_liked = in_array($post->id, $userLikes);
            return $post;
        });

        return [
            'posts' => $posts,
            'following_ids' => $followingIds,
            'channels' => [
                'timeline' => 'timeline',
                'user_timeline' => 'user.timeline.' . $user->id,
            ],
        ];
    }

    public function getPostUpdates(Post $post, User $user): array
    {
        $post->load([
            'user:id,name,username,avatar,verified,verification_type',
        ]);

        $isLiked = DB::table('likes')
            ->where('user_id', $user->id)
            ->where('likeable_id', $post->id)
            ->where('likeable_type', Post::class)
            ->exists();

        return [
            'post' => $post,
            'is_liked' => $isLiked,
            'channel' => 'post.' . $post->id,
        ];
    }
}
