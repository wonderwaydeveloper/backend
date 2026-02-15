<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class RealtimeTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_user_can_update_status_to_online()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/realtime/status', [
                'status' => 'online',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user_id',
                'is_online',
                'last_seen_at',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_online' => true,
        ]);
    }

    public function test_user_can_update_status_to_offline()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/realtime/status', [
                'status' => 'offline',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_online' => false,
        ]);
    }

    public function test_user_can_update_status_to_away()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/realtime/status', [
                'status' => 'away',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'is_online' => false,
        ]);
    }

    public function test_status_update_requires_valid_status()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/realtime/status', [
                'status' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_status_update_requires_authentication()
    {
        $this->withMiddleware();
        
        $response = $this->postJson('/api/realtime/status', [
            'status' => 'online',
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_get_online_users()
    {
        $this->user->update(['is_online' => true, 'last_seen_at' => now()]);
        $this->otherUser->update(['is_online' => true, 'last_seen_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/online-users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'username',
                        'avatar',
                        'verified',
                        'verification_type',
                        'is_online',
                        'last_seen_at',
                    ],
                ],
            ]);
    }

    public function test_online_users_excludes_offline_users()
    {
        $this->user->update(['is_online' => true, 'last_seen_at' => now()]);
        $this->otherUser->update(['is_online' => false, 'last_seen_at' => now()->subMinutes(10)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/online-users');

        $response->assertStatus(200);

        $data = $response->json('data');
        $userIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($this->user->id, $userIds);
        $this->assertNotContains($this->otherUser->id, $userIds);
    }

    public function test_online_users_excludes_stale_users()
    {
        $this->user->update(['is_online' => true, 'last_seen_at' => now()]);
        $this->otherUser->update(['is_online' => true, 'last_seen_at' => now()->subMinutes(10)]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/online-users');

        $response->assertStatus(200);

        $data = $response->json('data');
        $userIds = collect($data)->pluck('id')->toArray();

        $this->assertContains($this->user->id, $userIds);
        $this->assertNotContains($this->otherUser->id, $userIds);
    }

    public function test_user_can_get_specific_user_status()
    {
        $this->otherUser->update(['is_online' => true, 'last_seen_at' => now()]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/realtime/users/{$this->otherUser->id}/status");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user_id',
                'is_online',
                'last_seen_at',
            ])
            ->assertJson([
                'user_id' => $this->otherUser->id,
                'is_online' => true,
            ]);
    }

    public function test_user_can_get_live_timeline()
    {
        $this->user->following()->attach($this->otherUser->id);

        Post::factory()->count(3)->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/timeline');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'posts',
                'following_ids',
                'channels' => [
                    'timeline',
                    'user_timeline',
                ],
            ]);
    }

    public function test_live_timeline_only_shows_recent_posts()
    {
        $this->user->following()->attach($this->otherUser->id);

        Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
            'created_at' => now()->subMinutes(30),
        ]);

        Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
            'created_at' => now()->subHours(3),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/timeline');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
    }

    public function test_live_timeline_excludes_drafts()
    {
        $this->user->following()->attach($this->otherUser->id);

        Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => true,
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/timeline');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertEmpty($posts);
    }

    public function test_live_timeline_includes_own_posts()
    {
        Post::factory()->create([
            'user_id' => $this->user->id,
            'is_draft' => false,
            'created_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/realtime/timeline');

        $response->assertStatus(200);

        $posts = $response->json('posts');
        $this->assertCount(1, $posts);
    }

    public function test_user_can_get_post_updates()
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/realtime/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'post' => [
                    'id',
                    'user_id',
                    'content',
                    'user',
                ],
                'is_liked',
                'channel',
            ]);
    }

    public function test_post_updates_shows_like_status()
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
        ]);

        $post->likes()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/realtime/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'is_liked' => true,
            ]);
    }

    public function test_post_updates_includes_broadcast_channel()
    {
        $post = Post::factory()->create([
            'user_id' => $this->otherUser->id,
            'is_draft' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/realtime/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'channel' => "post.{$post->id}",
            ]);
    }

    public function test_realtime_endpoints_require_authentication()
    {
        $this->withMiddleware();
        
        $response = $this->getJson('/api/realtime/online-users');
        $response->assertStatus(401);

        $response = $this->getJson('/api/realtime/timeline');
        $response->assertStatus(401);
    }
}
