<?php

namespace Tests\Feature;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HashtagTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_trending_hashtags(): void
    {
        Hashtag::factory()->create(['name' => 'trending', 'posts_count' => 100]);
        Hashtag::factory()->create(['name' => 'popular', 'posts_count' => 50]);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/hashtags/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'slug', 'posts_count']
            ]);
    }

    public function test_can_view_hashtag_posts(): void
    {
        $hashtag = Hashtag::factory()->create();
        $post = Post::factory()->create();
        $hashtag->posts()->attach($post->id);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson("/api/hashtags/{$hashtag->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'hashtag' => ['id', 'name', 'slug'],
                'posts' => [
                    'data' => [
                        '*' => ['id', 'content']
                    ]
                ]
            ]);
    }

    public function test_can_search_hashtags(): void
    {
        Hashtag::factory()->create(['name' => 'laravel']);
        Hashtag::factory()->create(['name' => 'php']);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/hashtags/search?q=laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => ['id', 'name', 'slug']
            ]);
    }

    public function test_trending_hashtags_are_ordered_by_posts_count(): void
    {
        Hashtag::factory()->create(['name' => 'less', 'posts_count' => 10]);
        Hashtag::factory()->create(['name' => 'more', 'posts_count' => 100]);

        $response = $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/hashtags/trending');

        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals('more', $data[0]['name']);
    }
}
