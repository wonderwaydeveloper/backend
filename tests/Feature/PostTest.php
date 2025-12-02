<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PostTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_post()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'content' => 'This is a test post content.',
            'type' => 'post',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'content',
                    'user' => ['id', 'name'],
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'This is a test post content.',
            'type' => 'post',
        ]);
    }

    /** @test */
    public function post_can_have_media()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $images = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
        ];

        $response = $this->postJson('/api/posts', [
            'content' => 'Post with media',
            'media' => $images,
        ]);

        $response->assertStatus(201);

        $post = Post::latest()->first();
        $this->assertCount(2, $post->media);
    }

    /** @test */
    public function user_can_update_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'content' => 'Updated post content',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'content' => 'Updated post content',
                    'is_edited' => true,
                ],
            ]);
    }

    /** @test */
    public function user_cannot_update_other_users_post()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->putJson("/api/posts/{$post->id}", [
            'content' => 'Attempt to update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Post deleted successfully']);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /** @test */
    public function admin_can_delete_any_post()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    /** @test */
    public function user_can_like_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'liked' => true,
                    'like_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
            'likeable_type' => Post::class,
        ]);
    }

    /** @test */
    public function user_can_unlike_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        // First like the post
        $post->likes()->create(['user_id' => $user->id]);
        $post->increment('like_count');

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'liked' => false,
                    'like_count' => 0,
                ],
            ]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
        ]);
    }

    /** @test */
    public function user_can_repost()
    {
        $user = User::factory()->create();
        $originalPost = Post::factory()->create(['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$originalPost->id}/repost");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'original_post' => ['id'],
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'type' => 'quote',
            'original_post_id' => $originalPost->id,
        ]);

        $this->assertEquals(1, $originalPost->fresh()->repost_count);
    }

    /** @test */
    public function user_can_bookmark_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/posts/{$post->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['bookmarked' => true],
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'bookmarkable_id' => $post->id,
            'bookmarkable_type' => Post::class,
        ]);
    }

    /** @test */
    public function user_can_view_own_posts()
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/posts/user/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function user_can_view_personal_feed()
    {
        $user = User::factory()->create();
        $following = User::factory()->count(3)->create();

        // Follow users
        foreach ($following as $followed) {
            \App\Models\Follow::create([
                'follower_id' => $user->id,
                'following_id' => $followed->id,
                'approved_at' => now(),
            ]);

            // Create posts by followed users
            Post::factory()->count(2)->create(['user_id' => $followed->id]);
        }

        // Create own posts
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/posts/feed/personal');

        $response->assertStatus(200)
            ->assertJsonCount(8, 'data'); // 3 users * 2 posts + 2 own posts
    }

    /** @test */
    public function underage_user_cannot_view_sensitive_content()
    {
        $child = User::factory()->create(['is_underage' => true]);
        $post = Post::factory()->create(['is_sensitive' => true]);
        Sanctum::actingAs($child);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function adult_user_can_view_sensitive_content()
    {
        $adult = User::factory()->create(['is_underage' => false]);
        $post = Post::factory()->create(['is_sensitive' => true]);
        Sanctum::actingAs($adult);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function post_view_count_increments()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Sanctum::actingAs($user);

        $initialViews = $post->view_count;

        $response = $this->getJson("/api/posts/{$post->id}");
        $response->assertStatus(200);

        $this->assertEquals($initialViews + 1, $post->fresh()->view_count);
    }

    /** @test */
    public function user_can_create_reply_to_post()
    {
        $user = User::factory()->create();
        $parentPost = Post::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/posts', [
            'content' => 'This is a reply',
            'type' => 'reply',
            'parent_id' => $parentPost->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'reply',
                    'parent' => ['id' => $parentPost->id],
                ],
            ]);

        $this->assertEquals(1, $parentPost->fresh()->reply_count);
    }
}