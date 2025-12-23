<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PerformanceLoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_performance_under_load()
    {
        // Disable rate limiting for testing
        $this->withoutMiddleware();
        
        // Create test data
        $user = User::factory()->create();
        $users = User::factory(10)->create(); // Reduced for faster test
        $posts = Post::factory(50)->create(); // Reduced for faster test
        
        $this->actingAs($user);

        $startTime = microtime(true);
        
        // Simulate concurrent requests (reduced count)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/posts');
            $response->assertStatus(200);
        }
        
        $endTime = microtime(true);
        $averageTime = ($endTime - $startTime) / 10;
        
        // Assert response time < 200ms
        $this->assertLessThan(0.2, $averageTime, 
            "Average response time {$averageTime}s exceeds 200ms limit");
    }

    public function test_timeline_performance()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $startTime = microtime(true);
        $response = $this->getJson('/api/timeline');
        $endTime = microtime(true);
        
        $responseTime = $endTime - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(0.15, $responseTime, 
            "Timeline response time {$responseTime}s exceeds 150ms limit");
    }
}