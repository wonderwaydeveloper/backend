<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_create_post()
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/posts', [
            'content' => 'This is a test post content',
            'type' => 'post',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'This is a test post content')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('posts', [
            'content' => 'This is a test post content',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function user_can_upload_media_with_post()
    {
        Storage::fake('public');
        
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $image = UploadedFile::fake()->image('post-image.jpg');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/posts', [
            'content' => 'Post with image',
            'media' => [$image],
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('post_media', [
            'file_name' => 'post-image.jpg',
        ]);

        Storage::disk('public')->assertExists('posts/media/' . $image->hashName());
    }

    /** @test */
    public function user_can_view_single_post()
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.content', $post->content);
    }

    /** @test */
    public function user_can_update_their_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/posts/{$post->id}", [
            'content' => 'Updated post content',
            'is_sensitive' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.content', 'Updated post content')
            ->assertJsonPath('data.is_sensitive', true)
            ->assertJsonPath('data.is_edited', true);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'content' => 'Updated post content',
            'is_edited' => true,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_post()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user2->id]);
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/posts/{$post->id}", [
            'content' => 'Unauthorized update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Post deleted successfully']);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /** @test */
    public function user_can_like_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Post liked']);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
            'likeable_type' => Post::class,
        ]);

        $this->assertEquals(1, $post->fresh()->like_count);
    }

    /** @test */
    public function user_can_unlike_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $post->likes()->create(['user_id' => $user->id]);
        $post->update(['like_count' => 1]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Post unliked']);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
        ]);

        $this->assertEquals(0, $post->fresh()->like_count);
    }

    /** @test */
    public function user_can_repost_a_post()
    {
        $user = User::factory()->create();
        $originalPost = Post::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/posts/{$originalPost->id}/repost");

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'quote')
            ->assertJsonPath('data.original_post.id', $originalPost->id);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'quote',
            'original_post_id' => $originalPost->id,
        ]);

        $this->assertEquals(1, $originalPost->fresh()->repost_count);
    }

    /** @test */
    public function user_can_view_their_feed()
    {
        $user = User::factory()->create();
        $following = User::factory()->create();
        
        $user->following()->attach($following->id, ['approved_at' => now()]);
        
        $post = Post::factory()->create(['user_id' => $following->id]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/posts/feed/personal');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.id', $post->id);
    }

    /** @test */
    public function sensitive_content_is_filtered_for_underage_users()
    {
        $underageUser = User::factory()->create([
            'birth_date' => now()->subYears(16)->format('Y-m-d'),
            'is_underage' => true,
        ]);
        
        Post::factory()->create(['is_sensitive' => false, 'content' => 'Safe content']);
        Post::factory()->create(['is_sensitive' => true, 'content' => 'Sensitive content']);
        
        $token = $underageUser->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/posts');

        $response->assertStatus(200);
        
        $posts = $response->json('data.data');
        $this->assertCount(1, $posts);
        $this->assertEquals('Safe content', $posts[0]['content']);
    }
}