<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostDebugTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $this->token = $loginResponse->json('data.access_token');
    }

    public function test_debug_post_creation()
    {
        $postData = [
            'content' => 'Test post content',
            'type' => 'post'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/posts', $postData);

        dump('POST CREATE RESPONSE:');
        dump('Status: ' . $response->status());
        dump('Response: ', $response->json());
        
        // فقط status رو چک کن
        $response->assertStatus(200);
    }

    public function test_debug_post_like()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/posts/{$post->id}/like");

        dump('POST LIKE RESPONSE:');
        dump('Status: ' . $response->status());
        dump('Response: ', $response->json());
        
        $response->assertStatus(200);
    }

    public function test_debug_user_posts()
    {
        Post::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/posts/user/{$this->user->id}");

        dump('USER POSTS RESPONSE:');
        dump('Status: ' . $response->status());
        dump('Response: ', $response->json());
        
        $response->assertStatus(200);
    }
}