<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Hashtag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::create(['name' => 'search.basic']);
        Permission::create(['name' => 'search.advanced']);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->givePermissionTo('search.basic');
        $this->user->givePermissionTo('search.advanced');
    }

    public function test_search_posts_requires_authentication()
    {
        $response = $this->getJson('/api/search/posts?q=test');
        $response->assertStatus(401);
    }

    public function test_search_posts_with_query()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/search/posts?q=test');
        $response->assertStatus(200);
    }

    public function test_search_users_requires_authentication()
    {
        $response = $this->getJson('/api/search/users?q=john');
        $response->assertStatus(401);
    }

    public function test_search_users_with_query()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/search/users?q=john');
        $response->assertStatus(200);
    }

    public function test_search_hashtags_requires_authentication()
    {
        $response = $this->getJson('/api/search/hashtags?q=trending');
        $response->assertStatus(401);
    }

    public function test_trending_hashtags_returns_data()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/trending/hashtags');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta']);
    }

    public function test_trending_posts_returns_data()
    {
        $this->actingAs($this->user);
        
        $response = $this->getJson('/api/trending/posts');
        $response->assertStatus(200)
                 ->assertJsonStructure(['data', 'meta']);
    }

    public function test_user_suggestions_requires_authentication()
    {
        $response = $this->getJson('/api/suggestions/users');
        $response->assertStatus(401);
    }
}
