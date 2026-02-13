<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    public function test_trending_hashtags_requires_authentication()
    {
        $response = $this->getJson('/api/trending/hashtags');
        $response->assertStatus(401);
    }

    public function test_trending_posts_requires_authentication()
    {
        $response = $this->getJson('/api/trending/posts');
        $response->assertStatus(401);
    }

    public function test_trending_users_requires_authentication()
    {
        $response = $this->getJson('/api/trending/users');
        $response->assertStatus(401);
    }

    public function test_trending_hashtags_with_custom_limit()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/trending/hashtags?limit=5');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta']);
    }

    public function test_trending_posts_with_timeframe()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/trending/posts?timeframe=48');
        $response->assertStatus(200);
    }

    public function test_personalized_trending_requires_authentication()
    {
        $response = $this->getJson('/api/trending/personalized');
        $response->assertStatus(401);
    }

    public function test_trending_all_returns_combined_data()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/trending/all');
        $response->assertStatus(200)
                 ->assertJsonStructure(['hashtags', 'posts', 'users']);
    }
}
