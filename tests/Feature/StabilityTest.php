<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_core_apis_are_working()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test all critical endpoints
        $endpoints = [
            'GET /api/health' => 200,
            'GET /api/me' => 200,
            'GET /api/posts' => 200,
            'GET /api/timeline' => 200,
            'GET /api/notifications' => 200,
        ];

        foreach ($endpoints as $endpoint => $expectedStatus) {
            [$method, $url] = explode(' ', $endpoint);
            $response = $this->json($method, $url);
            $response->assertStatus($expectedStatus);
        }
    }

    public function test_database_relationships_integrity()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        // Test relationships
        $this->assertInstanceOf(User::class, $post->user);
        $this->assertTrue($user->posts->contains($post));
    }

    public function test_authentication_security()
    {
        // Test protected routes without auth
        $response = $this->getJson('/api/me');
        $response->assertStatus(401);

        // Test with valid auth
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->getJson('/api/me');
        $response->assertStatus(200);
    }

    public function test_data_validation()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Test invalid post creation
        $response = $this->postJson('/api/posts', []);
        $response->assertStatus(422);

        // Test valid post creation
        $response = $this->postJson('/api/posts', [
            'content' => 'Test post content'
        ]);
        $response->assertStatus(201);
    }
}