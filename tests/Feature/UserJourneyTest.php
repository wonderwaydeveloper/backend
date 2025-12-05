<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_user_journey()
    {
        // Register
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Integration User',
            'username' => 'journeyuser',
            'email' => 'journey@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1990-01-01',
        ])->assertStatus(200);

        $user = User::find($register['data']['user']['id']);
        $token = $register['data']['access_token'];

        Sanctum::actingAs($user);

        // Update profile
        $this->putJson('/api/users/me', [
            'bio' => 'Integration Bio',
            'location' => 'Test',
            'is_private' => false,
        ])->assertStatus(200);

        // Create post
        $post = $this->postJson('/api/posts', [
            'content' => 'My first post!',
        ])->assertStatus(201)['data'];

        // Cannot like own post
        $this->postJson("/api/posts/{$post['id']}/like")
            ->assertStatus(400);

        // Create second user
        $user2 = User::factory()->create();
        $user2Token = $user2->createToken('token')->plainTextToken;

        // user2 → like post
        $this->withToken($user2Token)
            ->postJson("/api/posts/{$post['id']}/like")
            ->assertStatus(200);

        // user2 → follow user1
        $this->withToken($user2Token)
            ->postJson("/api/users/{$user->id}/follow")
            ->assertStatus(200);

        // user2 → comment
        $this->withToken($user2Token)
            ->postJson('/api/comments', [
                'content' => 'Nice!',
                'commentable_type' => 'post',
                'commentable_id' => $post['id'],
            ])->assertStatus(201);

        // Create article
        $article = $this->postJson('/api/articles', [
            'title' => 'Test Article',
            'content' => 'Lorem ipsum',
            'excerpt' => 'short',
            'tags' => ['integration'],
            'status' => 'published',
        ])->assertStatus(201)['data'];

        // user2 bookmarks article
        $this->withToken($user2Token)
            ->postJson("/api/articles/{$article['id']}/bookmark")
            ->assertStatus(200);

        // Assertions
        $this->assertDatabaseHas('posts', ['id' => $post['id']]);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user2->id,
            'likeable_id' => $post['id'],
        ]);
        $this->assertDatabaseHas('comments', [
            'user_id' => $user2->id,
            'commentable_id' => $post['id'],
        ]);
        $this->assertDatabaseHas('articles', ['id' => $article['id']]);
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user2->id,
            'bookmarkable_id' => $article['id'],
        ]);
    }
}
