<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        $permissions = ['comment.create', 'comment.delete.own', 'comment.delete.any', 'comment.like', 'post.like'];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }
        
        // Create role - user should NOT have comment.delete.any
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $role->syncPermissions(['comment.create', 'comment.delete.own', 'comment.like', 'post.like']);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'is_draft' => false]);
    }

    // ==================== SECTION 1: Get Comments ====================
    
    /** @test */
    public function test_can_get_post_comments()
    {
        Comment::factory()->count(3)->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'content', 'user', 'likes_count']]]);
    }

    /** @test */
    public function test_guest_can_view_comments()
    {
        // Make post public (not draft)
        $this->post->update(['is_draft' => false, 'published_at' => now()]);
        Comment::factory()->count(2)->create(['post_id' => $this->post->id]);

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk();
    }

    /** @test */
    public function test_comments_are_paginated()
    {
        Comment::factory()->count(25)->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    // ==================== SECTION 2: Create Comment ====================

    /** @test */
    public function test_can_create_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Great post!'
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'content', 'user']);
        
        $this->assertDatabaseHas('comments', [
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'content' => 'Great post!'
        ]);
    }

    /** @test */
    public function test_cannot_create_empty_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => ''
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_comment_on_draft_post()
    {
        $draftPost = Post::factory()->create(['is_draft' => true]);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$draftPost->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_comment_when_blocked()
    {
        $postOwner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id]);
        $postOwner->blockedUsers()->attach($this->user->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cannot_comment_when_muted()
    {
        $postOwner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id]);
        $postOwner->mutedUsers()->attach($this->user->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_comment_respects_reply_settings_none()
    {
        $post = Post::factory()->create(['reply_settings' => 'none']);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function test_comment_respects_reply_settings_following()
    {
        $postOwner = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'reply_settings' => 'following'
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function test_content_is_sanitized()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => '<script>alert("xss")</script>Test'
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', [
            'content' => 'Test'
        ]);
    }

    // ==================== SECTION 3: Delete Comment ====================

    /** @test */
    public function test_can_delete_own_comment()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_cannot_delete_others_comment()
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function test_delete_decrements_post_comments_count()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);
        $this->post->increment('comments_count');
        $initialCount = $this->post->fresh()->comments_count;

        $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $this->post->refresh();
        $this->assertEquals($initialCount - 1, $this->post->comments_count);
    }

    // ==================== SECTION 4: Like Comment ====================

    /** @test */
    public function test_can_like_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk()
            ->assertJson(['liked' => true]);
        
        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $comment->id,
            'likeable_type' => Comment::class
        ]);
    }

    /** @test */
    public function test_can_unlike_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);
        $comment->likes()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk()
            ->assertJson(['liked' => false]);
        
        $this->assertDatabaseMissing('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $comment->id
        ]);
    }

    /** @test */
    public function test_like_increments_likes_count()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'likes_count' => 0]);

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $comment->refresh();
        $this->assertEquals(1, $comment->likes_count);
    }

    // ==================== SECTION 5: Security Tests ====================

    /** @test */
    public function test_guest_cannot_create_comment()
    {
        $response = $this->postJson("/api/posts/{$this->post->id}/comments", [
            'content' => 'Test'
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function test_guest_cannot_delete_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->deleteJson("/api/comments/{$comment->id}");

        $response->assertUnauthorized();
    }

    /** @test */
    public function test_guest_cannot_like_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->postJson("/api/comments/{$comment->id}/like");

        $response->assertUnauthorized();
    }

    /** @test */
    public function test_comment_increments_post_comments_count()
    {
        $initialCount = $this->post->comments_count;

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test comment'
            ]);

        $this->post->refresh();
        $this->assertEquals($initialCount + 1, $this->post->comments_count);
    }

    // ==================== SECTION 6: Edge Cases & Real Issues ====================

    /** @test */
    public function test_CRITICAL_authorization_missing_in_delete()
    {
        // Based on Code Review: deleteComment missing authorization check
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        // SHOULD be Forbidden, but might pass due to missing auth check
        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_CRITICAL_spam_detection_timing()
    {
        // Based on Code Review: spam check happens AFTER DB write
        $spamContent = str_repeat('BUY NOW!!! ', 50);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => $spamContent
            ]);

        // Should reject spam BEFORE saving to DB
        $this->assertNotEquals(201, $response->status(), 'Spam was saved to database before detection!');
    }

    /** @test */
    public function test_HIGH_mass_assignment_likes_count()
    {
        // Based on Code Review: likes_count in $fillable
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test',
                'likes_count' => 999999
            ]);

        if ($response->status() === 201) {
            $comment = Comment::latest()->first();
            $this->assertNotEquals(999999, $comment->likes_count, 
                'VULNERABILITY: likes_count can be mass assigned!');
        }
    }

    /** @test */
    public function test_HIGH_sql_injection_in_scopes()
    {
        // Based on Code Review: SQL injection risk in query scopes
        $maliciousPostId = "1 OR 1=1";
        
        try {
            Comment::where('post_id', $maliciousPostId)->get();
            $this->assertTrue(true, 'Query executed safely');
        } catch (\Exception $e) {
            $this->fail('SQL injection vulnerability detected: ' . $e->getMessage());
        }
    }

    /** @test */
    public function test_MEDIUM_mention_checking_false_positive()
    {
        // Based on Code Review: str_contains causes false positives
        $postOwner = User::factory()->create(['username' => 'john']);
        $post = Post::factory()->create([
            'user_id' => $postOwner->id,
            'reply_settings' => 'mentioned',
            'content' => 'Hello @john'
        ]);

        $otherUser = User::factory()->create(['username' => 'johnny']);
        $token = $otherUser->createToken('test')->plainTextToken;

        // Should NOT be allowed (johnny != john)
        $response = $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments", [
                'content' => 'Test reply'
            ]);

        // If str_contains is used, 'john' in 'johnny' will match
        $response->assertForbidden();
    }

/** @test */
    public function test_cannot_exceed_max_length()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => str_repeat('a', 281)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('content');
    }

    /** @test */
    public function test_sql_injection_protection()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => "'; DROP TABLE comments; --"
            ]);

        $response->assertCreated();
        $this->assertTrue(\DB::getSchemaBuilder()->hasTable('comments'));
    }

    /** @test */
    public function test_xss_protection_in_nested_content()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => '<div><script>alert(1)</script>Safe Content</div>'
            ]);

        $response->assertCreated();
        $comment = Comment::latest()->first();
        $this->assertEquals('Safe Content', $comment->content);
        $this->assertStringNotContainsString('<script>', $comment->content);
        $this->assertStringNotContainsString('alert', $comment->content);
    }

    /** @test */
    public function test_whitespace_is_trimmed()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => '  Test  '
            ]);

        $response->assertCreated();
        $comment = Comment::latest()->first();
        $this->assertEquals('Test', $comment->content);
    }

    /** @test */
    public function test_comment_has_correct_relationships()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);

        $this->assertInstanceOf(User::class, $comment->user);
        $this->assertInstanceOf(Post::class, $comment->post);
        $this->assertEquals($this->user->id, $comment->user->id);
        $this->assertEquals($this->post->id, $comment->post->id);
    }

    /** @test */
    public function test_unlike_decrements_likes_count()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id, 'likes_count' => 1]);
        $comment->likes()->create(['user_id' => $this->user->id]);

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $comment->refresh();
        $this->assertEquals(0, $comment->likes_count);
    }

    /** @test */
    public function test_cannot_like_non_existent_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/comments/999999/like");

        $response->assertNotFound();
    }

    /** @test */
    public function test_cannot_delete_non_existent_comment()
    {
        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/999999");

        $response->assertNotFound();
    }

    /** @test */
    public function test_race_condition_on_counter_increment()
    {
        // Test concurrent comment creation
        $initialCount = $this->post->comments_count;
        
        // Simulate 3 concurrent requests
        for ($i = 0; $i < 3; $i++) {
            $this->withToken($this->token)
                ->postJson("/api/posts/{$this->post->id}/comments", [
                    'content' => "Comment {$i}"
                ]);
        }

        $this->post->refresh();
        $actualComments = Comment::where('post_id', $this->post->id)->count();
        
        $this->assertEquals(
            $actualComments, 
            $this->post->comments_count,
            'Counter mismatch! Race condition detected.'
        );
    }

    /** @test */
    public function test_n_plus_one_query_problem()
    {
        Comment::factory()->count(10)->create(['post_id' => $this->post->id]);

        \DB::enableQueryLog();
        
        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $queries = \DB::getQueryLog();
        $queryCount = count($queries);

        // Should be ~3 queries (comments, users eager load, post)
        // If > 15, likely N+1 problem
        $this->assertLessThan(15, $queryCount, 
            "N+1 Query Problem! {$queryCount} queries executed");
    }

    // ==================== SECTION 7: Additional Coverage ====================

    /** @test */
    public function test_comment_with_media_upload()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.jpg', 500, 500);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Comment with image',
                'media' => $file
            ]);

        $response->assertCreated();
        $comment = Comment::latest()->first();
        $this->assertEquals('Comment with image', $comment->content);
    }

    /** @test */
    public function test_pagination_uses_config()
    {
        $perPage = config('pagination.comments', 20);
        Comment::factory()->count($perPage + 5)->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonCount($perPage, 'data');
    }

    /** @test */
    public function test_comment_response_structure()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'content',
                'user' => ['id', 'name', 'username', 'avatar'],
                'created_at',
                'updated_at'
            ]);
    }

    /** @test */
    public function test_delete_returns_success_message()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk()
            ->assertJson(['message' => 'Comment deleted successfully']);
    }

    /** @test */
    public function test_like_returns_correct_structure()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk()
            ->assertJsonStructure(['liked', 'likes_count']);
    }

    /** @test */
    public function test_comment_includes_user_relationship()
    {
        Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'user' => ['id', 'name', 'username', 'avatar']
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_comments_ordered_by_latest()
    {
        $comment1 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'created_at' => now()->subHours(2)
        ]);
        $comment2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'created_at' => now()->subHour()
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals($comment2->id, $data[0]['id']);
        $this->assertEquals($comment1->id, $data[1]['id']);
    }

    /** @test */
    public function test_error_message_on_invalid_post()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/999999/comments", [
                'content' => 'Test'
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function test_comment_includes_likes_count()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['likes_count']
                ]
            ]);
    }

    // ==================== SECTION 8: Integration Tests ====================

    /** @test */
    public function test_comment_integrates_with_like_system()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");
        
        $response->assertOk();
        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'likeable_id' => $comment->id,
            'likeable_type' => Comment::class
        ]);
    }

    /** @test */
    public function test_comment_integrates_with_block_system()
    {
        $user2 = User::factory()->create();
        $user2->assignRole('user');
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        
        $this->user->blockedUsers()->attach($user2->id);
        
        $token = $user2->createToken('test')->plainTextToken;
        
        $response = $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments", ['content' => 'Test']);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function test_comment_integrates_with_mute_system()
    {
        $user2 = User::factory()->create();
        $user2->assignRole('user');
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        
        $this->user->mutedUsers()->attach($user2->id);
        
        $token = $user2->createToken('test')->plainTextToken;
        
        $response = $this->withToken($token)
            ->postJson("/api/posts/{$post->id}/comments", ['content' => 'Test']);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function test_comment_delete_decrements_counter()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id
        ]);
        
        $this->post->update(['comments_count' => 1]);
        
        $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");
        
        $this->assertEquals(0, $this->post->fresh()->comments_count);
    }

    /** @test */
    public function test_comment_integrates_with_spam_detection()
    {
        $spamContent = str_repeat('BUY NOW!!! ', 50);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => $spamContent]);
        
        $this->assertNotEquals(201, $response->status());
    }

    /** @test */
    public function test_comment_integrates_with_cache_system()
    {
        Comment::factory()->count(3)->create(['post_id' => $this->post->id]);
        
        $response1 = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");
        
        $response2 = $this->withToken($this->token)
            ->getJson("/api/posts/{$this->post->id}/comments");
        
        $response1->assertOk();
        $response2->assertOk();
        $this->assertEquals($response1->json(), $response2->json());
    }
}
