<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Post;
use App\Models\Hashtag;
use Illuminate\Support\Facades\Cache;

class CacheWarmup extends Command
{
    protected $signature = 'cache:warmup';
    protected $description = 'Warmup cache with frequently accessed data';

    public function handle()
    {
        $this->info('🔥 Starting cache warmup...');

        // Warmup trending hashtags
        $this->warmupTrendingHashtags();
        
        // Warmup popular posts
        $this->warmupPopularPosts();
        
        // Warmup user suggestions
        $this->warmupUserSuggestions();

        $this->info('✅ Cache warmup completed');
    }

    private function warmupTrendingHashtags()
    {
        $this->info('📈 Warming up trending hashtags...');
        
        $trending = Hashtag::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit(20)
            ->get();
            
        Cache::put('trending_hashtags', $trending, now()->addHours(1));
        $this->line('  ✓ Trending hashtags cached');
    }

    private function warmupPopularPosts()
    {
        $this->info('🔥 Warming up popular posts...');
        
        $popular = Post::withCount(['likes', 'comments'])
            ->orderBy('likes_count', 'desc')
            ->limit(50)
            ->get();
            
        Cache::put('popular_posts', $popular, now()->addMinutes(30));
        $this->line('  ✓ Popular posts cached');
    }

    private function warmupUserSuggestions()
    {
        $this->info('👥 Warming up user suggestions...');
        
        $suggestions = User::withCount('followers')
            ->orderBy('followers_count', 'desc')
            ->limit(20)
            ->get();
            
        Cache::put('user_suggestions', $suggestions, now()->addHours(2));
        $this->line('  ✓ User suggestions cached');
    }
}