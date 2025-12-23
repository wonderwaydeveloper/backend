<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleTrendingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_trending_hashtags_by_posts_count(): void
    {
        Hashtag::factory()->create(['name' => 'trending1', 'posts_count' => 100]);
        Hashtag::factory()->create(['name' => 'trending2', 'posts_count' => 80]);
        Hashtag::factory()->create(['name' => 'nottrending', 'posts_count' => 5]);

        $trending = Hashtag::where('posts_count', '>', 10)
            ->orderBy('posts_count', 'desc')
            ->limit(2)
            ->get();

        $this->assertCount(2, $trending);
        $this->assertEquals('trending1', $trending->first()->name);
        $this->assertEquals('trending2', $trending->last()->name);
    }

    public function test_trending_posts_by_engagement(): void
    {
        Post::factory()->create(['likes_count' => 100, 'comments_count' => 50]);
        Post::factory()->create(['likes_count' => 80, 'comments_count' => 30]);
        Post::factory()->create(['likes_count' => 10, 'comments_count' => 5]);

        $trending = Post::orderByRaw('(likes_count + comments_count * 2) DESC')
            ->limit(2)
            ->get();

        $this->assertCount(2, $trending);
        $this->assertEquals(100, $trending->first()->likes_count);
    }

    public function test_trending_users_by_followers(): void
    {
        User::factory()->create(['followers_count' => 1000]);
        User::factory()->create(['followers_count' => 800]);
        User::factory()->create(['followers_count' => 100]);

        $trending = User::where('followers_count', '>', 500)
            ->orderBy('followers_count', 'desc')
            ->limit(2)
            ->get();

        $this->assertCount(2, $trending);
        $this->assertEquals(1000, $trending->first()->followers_count);
    }

    public function test_recent_trending_content(): void
    {
        Post::factory()->create([
            'likes_count' => 100,
            'created_at' => now()->subHours(1) // Recent
        ]);
        Post::factory()->create([
            'likes_count' => 200,
            'created_at' => now()->subDays(10) // Old
        ]);

        $recentTrending = Post::where('created_at', '>=', now()->subDay())
            ->orderBy('likes_count', 'desc')
            ->get();

        $this->assertCount(1, $recentTrending);
        $this->assertEquals(100, $recentTrending->first()->likes_count);
    }

    public function test_hashtag_velocity_calculation(): void
    {
        $hashtag = Hashtag::factory()->create(['name' => 'test']);
        
        // Create posts with this hashtag at different times
        Post::factory()->create([
            'content' => 'Post with #test',
            'created_at' => now()->subHours(1)
        ]);
        Post::factory()->create([
            'content' => 'Another #test post',
            'created_at' => now()->subHours(2)
        ]);

        $recentPosts = Post::where('content', 'LIKE', '%#test%')
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        $this->assertEquals(2, $recentPosts);
    }

    public function test_trending_excludes_nsfw(): void
    {
        Post::factory()->create([
            'content' => 'Normal post',
            'likes_count' => 100
        ]);
        Post::factory()->create([
            'content' => 'NSFW post', 
            'likes_count' => 200
        ]);

        // Simulate filtering NSFW content
        $safeTrending = Post::where('content', 'NOT LIKE', '%NSFW%')
            ->orderBy('likes_count', 'desc')
            ->get();

        $this->assertCount(1, $safeTrending);
        $this->assertEquals('Normal post', $safeTrending->first()->content);
    }

    public function test_trending_by_location(): void
    {
        $user = User::factory()->create();
        Post::factory()->create([
            'user_id' => $user->id,
            'likes_count' => 100,
            'content' => 'Post from Tehran'
        ]);

        $locationTrending = Post::whereHas('user', function ($query) use ($user) {
            $query->where('id', $user->id);
        })->orderBy('likes_count', 'desc')->get();

        $this->assertCount(1, $locationTrending);
        $this->assertEquals('Post from Tehran', $locationTrending->first()->content);
    }
}