<?php

namespace App\Observers;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostObserver
{
    public function created(Post $post): void
    {
        if (! $post->is_draft) {
            $this->clearCaches($post);
        }
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged('is_draft') && ! $post->is_draft) {
            $this->clearCaches($post);
        }

        if ($post->wasChanged(['content', 'image', 'gif_url'])) {
            $this->clearUserCaches($post->user_id);
        }
    }

    public function deleted(Post $post): void
    {
        $this->clearCaches($post);
    }

    private function clearCaches(Post $post): void
    {
        // Clear specific post cache
        Cache::forget("post:{$post->id}");
        
        // Clear trending hashtags
        Cache::forget('trending_hashtags');

        // Clear public posts cache (first 10 pages)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("public_posts:{$page}:20");
        }

        // Clear user timeline caches
        $this->clearUserCaches($post->user_id);

        // Clear followers' timeline caches
        $followerIds = $post->user->followers()->pluck('users.id');
        foreach ($followerIds as $followerId) {
            $this->clearUserCaches($followerId);
        }
    }

    private function clearUserCaches(int $userId): void
    {
        Cache::forget("timeline:user:{$userId}");
        Cache::forget("timeline:{$userId}:20");
        Cache::forget("following:{$userId}");
        
        // Clear timeline cache for multiple pages
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("timeline:user:{$userId}:page:{$page}");
        }
    }
}
