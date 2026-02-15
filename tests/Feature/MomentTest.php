<?php

namespace Tests\Feature;

use App\Models\Moment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;

class MomentTest extends TestCase
{
    use RefreshDatabase, WithoutMiddleware;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_create_moment(): void
    {
        $response = $this->postJson('/api/moments', [
            'title' => 'Test Moment',
            'description' => 'Test Description',
            'privacy' => 'public',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('moments', ['title' => 'Test Moment']);
    }

    public function test_can_get_moments(): void
    {
        Moment::factory()->count(3)->create(['privacy' => 'public']);

        $response = $this->getJson('/api/moments');

        $response->assertStatus(200);
    }

    public function test_can_get_single_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/moments/{$moment->id}");

        $response->assertStatus(200);
    }

    public function test_can_update_own_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/moments/{$moment->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('moments', ['title' => 'Updated Title']);
    }

    public function test_can_delete_own_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/moments/{$moment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('moments', ['id' => $moment->id]);
    }

    public function test_can_add_post_to_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);
        $post = Post::factory()->create();

        $response = $this->postJson("/api/moments/{$moment->id}/posts", [
            'post_id' => $post->id,
            'position' => 0,
        ]);

        $response->assertStatus(200);
    }

    public function test_can_remove_post_from_moment(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id]);
        $post = Post::factory()->create();
        $moment->addPost($post->id, 0);

        $response = $this->deleteJson("/api/moments/{$moment->id}/posts/{$post->id}");

        $response->assertStatus(200);
    }

    public function test_can_get_my_moments(): void
    {
        Moment::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/moments/my-moments');

        $response->assertStatus(200);
    }

    public function test_can_get_featured_moments(): void
    {
        Moment::factory()->count(2)->create(['is_featured' => true, 'privacy' => 'public']);

        $response = $this->getJson('/api/moments/featured');

        $response->assertStatus(200);
    }

    public function test_cannot_update_others_moment(): void
    {
        $otherUser = User::factory()->create();
        $moment = Moment::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/moments/{$moment->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_view_private_moment_of_others(): void
    {
        $otherUser = User::factory()->create();
        $moment = Moment::factory()->create([
            'user_id' => $otherUser->id,
            'privacy' => 'private',
        ]);

        $response = $this->getJson("/api/moments/{$moment->id}");

        $response->assertStatus(404);
    }

    public function test_title_is_required(): void
    {
        $response = $this->postJson('/api/moments', [
            'description' => 'Test',
        ]);

        $response->assertStatus(422);
    }
}
