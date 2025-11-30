<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DebugResponseTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_all_responses()
    {
        // ایجاد داده تست
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        echo "\n=== DEBUGGING API RESPONSE STRUCTURES ===\n";

        // ۱. تست posts
        $response = $this->getJson('/api/posts');
        echo "\n1. GET /api/posts - Status: " . $response->status() . "\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

        // ۲. تست single post
        $response = $this->getJson("/api/posts/{$post->id}");
        echo "\n2. GET /api/posts/{$post->id} - Status: " . $response->status() . "\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

        // ۳. تست search
        $response = $this->getJson('/api/search?q=test');
        echo "\n3. GET /api/search?q=test - Status: " . $response->status() . "\n";
        if ($response->status() === 200) {
            echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "ERROR: " . $response->getContent() . "\n";
        }

        // ۴. تست user profile
        $response = $this->getJson("/api/users/{$user->id}");
        echo "\n4. GET /api/users/{$user->id} - Status: " . $response->status() . "\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";

        echo "\n=== END DEBUG ===\n";
    }
}