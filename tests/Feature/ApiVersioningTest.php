<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_v1_works(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/posts');

        $response->assertStatus(200)
            ->assertHeader('API-Version', 'v1')
            ->assertHeader('API-Supported-Versions', 'v1, v2');
    }

    public function test_api_v2_enhanced_search(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts?q=test');

        $response->assertStatus(200)
            ->assertHeader('API-Version', 'v2')
            ->assertJsonStructure([
                'data',
                'meta' => ['count']
            ]);
    }

    public function test_api_v2_user_search(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/users?q=john');

        $response->assertStatus(200)
            ->assertHeader('API-Version', 'v2');
    }

    public function test_api_versioning_middleware_sets_version(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts?q=test');

        $response->assertHeader('API-Version', 'v2');
    }

    public function test_search_validation_works(): void
    {
        $user = User::factory()->create();

        // Test missing query
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_search_with_filters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts?q=test&has_media=1&limit=10');

        $response->assertStatus(200);
    }

    public function test_search_query_length_validation(): void
    {
        $user = User::factory()->create();

        // Test query too short
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts?q=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);

        // Test query too long
        $longQuery = str_repeat('a', 101);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v2/search/posts?q=' . $longQuery);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_unauthenticated_cannot_access_v2_search(): void
    {
        $response = $this->getJson('/api/v2/search/posts?q=test');

        $response->assertStatus(401);
    }
}