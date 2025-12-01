<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_comment_on_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/comments', [
            'content' => 'This is a test comment',
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'This is a test comment')
            ->assertJsonPath('data.user.id', $user->id);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a test comment',
            'user_id' => $user->id,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);

        $this->assertEquals(1, $post->fresh()->comment_count);
    }

    /** @test */
    public function user_can_reply_to_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $parentComment = Comment::factory()->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/comments/{$parentComment->id}/reply", [
            'content' => 'This is a reply comment',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.content', 'This is a reply comment')
            ->assertJsonPath('data.parent.id', $parentComment->id);

        $this->assertDatabaseHas('comments', [
            'content' => 'This is a reply comment',
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
        ]);

        $this->assertEquals(1, $parentComment->fresh()->reply_count);
    }

    /** @test */
    public function user_can_like_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/comments/{$comment->id}/like");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comment liked']);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $comment->id,
            'likeable_type' => Comment::class,
        ]);

        $this->assertEquals(1, $comment->fresh()->like_count);
    }

    /** @test */
    public function comment_can_be_deleted_by_author()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comment deleted successfully']);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function admin_can_delete_any_comment()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $comment = Comment::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }
}