<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_comment_on_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/comments', [
            'content' => 'This is a comment on the post.',
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'content',
                    'user' => ['id', 'name'],
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
            'content' => 'This is a comment on the post.',
        ]);

        $this->assertEquals(1, $post->fresh()->comment_count);
    }

    /** @test */
    public function user_can_comment_on_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['status' => 'published']);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/comments', [
            'content' => 'Great article!',
            'commentable_type' => 'article',
            'commentable_id' => $article->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'commentable_id' => $article->id,
            'commentable_type' => Article::class,
        ]);

        $this->assertEquals(1, $article->fresh()->comment_count);
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
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/comments/{$parentComment->id}/reply", [
            'content' => 'This is a reply to the comment.',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'parent' => ['id' => $parentComment->id],
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'parent_id' => $parentComment->id,
            'commentable_id' => $post->id,
        ]);

        $this->assertEquals(1, $parentComment->fresh()->reply_count);
    }

    /** @test */
    public function user_can_update_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Updated comment content',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'content' => 'Updated comment content',
                    'is_edited' => true,
                ],
            ]);
    }

    /** @test */
    public function user_cannot_update_other_users_comment()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $response = $this->putJson("/api/comments/{$comment->id}", [
            'content' => 'Attempt to update',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_own_comment()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);
        Sanctum::actingAs($user);

        $initialCount = $post->comment_count;

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Comment deleted successfully']);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertEquals($initialCount - 1, $post->fresh()->comment_count);
    }

    /** @test */
    public function admin_can_delete_any_comment()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function user_can_like_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'liked' => true,
                    'like_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $comment->id,
            'likeable_type' => Comment::class,
        ]);
    }

    /** @test */
    public function user_cannot_like_own_comment()
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertStatus(400);
    }

    /** @test */
    public function user_can_view_comments_for_post()
    {
        $post = Post::factory()->create();
        Comment::factory()->count(5)->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/comments?commentable_type=post&commentable_id={$post->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function user_cannot_comment_on_private_content_without_access()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $privateUser->id]);

        Sanctum::actingAs($otherUser);

        $response = $this->postJson('/api/comments', [
            'content' => 'Trying to comment',
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function banned_user_cannot_comment()
    {
        $bannedUser = User::factory()->create(['is_banned' => true]);
        $post = Post::factory()->create();

        Sanctum::actingAs($bannedUser);

        $response = $this->postJson('/api/comments', [
            'content' => 'Comment from banned user',
            'commentable_type' => 'post',
            'commentable_id' => $post->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function deleted_comments_are_not_shown()
    {
        $post = Post::factory()->create();
        $activeComment = Comment::factory()->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);
        
        $deletedComment = Comment::factory()->create([
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);
        $deletedComment->delete();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/comments?commentable_type=post&commentable_id={$post->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Only active comment
    }
}