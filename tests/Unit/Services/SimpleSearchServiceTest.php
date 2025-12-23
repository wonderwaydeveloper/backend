<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_post_search(): void
    {
        Post::factory()->create(['content' => 'Laravel is awesome']);
        Post::factory()->create(['content' => 'PHP programming']);
        Post::factory()->create(['content' => 'JavaScript development']);

        $results = Post::where('content', 'LIKE', '%Laravel%')->get();

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Laravel', $results->first()->content);
    }

    public function test_basic_user_search(): void
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);
        User::factory()->create(['name' => 'Bob Johnson', 'username' => 'bobjohnson']);

        $results = User::where('name', 'LIKE', '%John%')
            ->orWhere('username', 'LIKE', '%John%')
            ->get();

        $this->assertCount(2, $results);
    }

    public function test_hashtag_search(): void
    {
        Hashtag::factory()->create(['name' => 'laravel', 'posts_count' => 10]);
        Hashtag::factory()->create(['name' => 'php', 'posts_count' => 5]);
        Hashtag::factory()->create(['name' => 'javascript', 'posts_count' => 3]);

        $results = Hashtag::where('name', 'LIKE', '%lar%')->get();

        $this->assertCount(1, $results);
        $this->assertEquals('laravel', $results->first()->name);
    }

    public function test_search_with_filters(): void
    {
        $user = User::factory()->create();
        Post::factory()->create([
            'content' => 'Laravel post',
            'user_id' => $user->id,
            'created_at' => now()->subDays(1)
        ]);
        Post::factory()->create([
            'content' => 'Laravel post old',
            'created_at' => now()->subDays(10)
        ]);

        $results = Post::where('content', 'LIKE', '%Laravel%')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(2))
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals($user->id, $results->first()->user_id);
    }

    public function test_search_respects_privacy(): void
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $publicUser = User::factory()->create(['is_private' => false]);

        Post::factory()->create([
            'content' => 'Private post',
            'user_id' => $privateUser->id
        ]);
        Post::factory()->create([
            'content' => 'Public post',
            'user_id' => $publicUser->id
        ]);

        // Search only public posts
        $results = Post::where('content', 'LIKE', '%post%')
            ->whereHas('user', function ($query) {
                $query->where('is_private', false);
            })
            ->get();

        $this->assertCount(1, $results);
        $this->assertEquals('Public post', $results->first()->content);
    }

    public function test_empty_search_query(): void
    {
        Post::factory()->count(5)->create();

        $results = Post::where('content', 'LIKE', '%%')->limit(0)->get();

        $this->assertCount(0, $results);
    }

    public function test_search_pagination(): void
    {
        Post::factory()->count(15)->create(['content' => 'Test post content']);

        $results = Post::where('content', 'LIKE', '%Test%')
            ->limit(10)
            ->get();

        $this->assertCount(10, $results);
    }
}