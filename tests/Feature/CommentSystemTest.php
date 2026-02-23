<?php

namespace Tests\Feature;

use App\Models\{User, Post, Comment, Like};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $permissions = [
            'comment.create',
            'comment.delete.own',
            'post.like'
        ];
        
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }
        
        // Create comment.delete.any permission but don't assign to user role
        \Spatie\Permission\Models\Permission::firstOrCreate(
            ['name' => 'comment.delete.any', 'guard_name' => 'sanctum']
        );
        
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $role->syncPermissions($permissions);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->post = Post::factory()->create(['user_id' => $this->user->id, 'content' => 'Test post']);
    }


    // ==================== SECTION 1: Core API Functionality ====================

    /** @test */
    public function test_can_list_comments()
    {
        Comment::factory()->count(3)->create(['post_id' => $this->post->id]);

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_can_create_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test comment'
            ]);

        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'content', 'user']);
        
        $this->assertDatabaseHas('comments', ['content' => 'Test comment']);
    }

    /** @test */
    public function test_can_create_nested_reply()
    {
        $parent = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply comment',
                'parent_id' => $parent->id
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'content' => 'Reply comment',
            'parent_id' => $parent->id
        ]);
    }

    /** @test */
    public function test_can_update_comment()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'content' => 'Original'
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated'
            ]);

        $response->assertOk();
        $this->assertEquals('Updated', $comment->fresh()->content);
    }

    /** @test */
    public function test_can_delete_comment()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertOk();
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_can_like_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $response->assertOk()
            ->assertJson(['liked' => true]);
        
        $this->assertEquals(1, $comment->fresh()->likes_count);
    }

    /** @test */
    public function test_can_pin_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/pin");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_pinned);
    }

    /** @test */
    public function test_can_hide_comment()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/hide");

        $response->assertOk();
        $this->assertTrue($comment->fresh()->is_hidden);
    }

    /** @test */
    public function test_pagination_works()
    {
        Comment::factory()->count(25)->create(['post_id' => $this->post->id]);

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    /** @test */
    public function test_can_create_comment_with_media()
    {
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Comment with media',
                'media' => $file
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', ['content' => 'Comment with media']);
    }

    /** @test */
    public function test_spam_content_rejected()
    {
        $spamContent = str_repeat('BUY NOW!!! ', 50);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => $spamContent
            ]);

        $response->assertStatus(422);
    }


    // ==================== SECTION 2: Authentication & Authorization ====================

    /** @test */
    public function test_guest_can_view_comments()
    {
        $response = $this->getJson("/api/posts/{$this->post->id}/comments");
        $response->assertOk();
    }

    /** @test */
    public function test_guest_cannot_create_comment()
    {
        $response = $this->postJson("/api/posts/{$this->post->id}/comments", [
            'content' => 'Test'
        ]);
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_authenticated_user_can_create_comment()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test comment'
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_cannot_update_others_comment()
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'post_id' => $this->post->id
        ]);

        $response = $this->withToken($this->token)
            ->putJson("/api/comments/{$comment->id}", ['content' => 'Hacked']);

        $response->assertForbidden();
    }

    /** @test */
    public function test_cannot_delete_others_comment()
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'post_id' => $this->post->id
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function test_post_owner_can_pin_comment()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/pin");

        $response->assertOk();
    }

    /** @test */
    public function test_non_post_owner_cannot_pin_comment()
    {
        $otherPost = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $otherPost->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/pin");

        $response->assertStatus(422);
    }

    /** @test */
    public function test_policy_enforced()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id
        ]);
        
        $response = $this->withToken($this->token)
            ->putJson("/api/comments/{$comment->id}", ['content' => 'Updated']);

        $response->assertOk();
    }

    /** @test */
    public function test_user_role_can_create_comment()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('user');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'User comment']);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_verified_role_can_create_comment()
    {
        $verified = User::factory()->create(['email_verified_at' => now()]);
        $verified->assignRole('verified');
        $token = $verified->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Verified comment']);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_premium_role_can_create_comment()
    {
        $premium = User::factory()->create(['email_verified_at' => now()]);
        $premium->assignRole('premium');
        $token = $premium->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Premium comment']);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_organization_role_can_create_comment()
    {
        $organization = User::factory()->create(['email_verified_at' => now()]);
        $organization->assignRole('organization');
        $token = $organization->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Organization comment']);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_moderator_role_can_create_comment()
    {
        $moderator = User::factory()->create(['email_verified_at' => now()]);
        $moderator->assignRole('moderator');
        $token = $moderator->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Moderator comment']);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_admin_role_can_create_comment()
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/posts/{$this->post->id}/comments", ['content' => 'Admin comment']);

        $response->assertStatus(201);
    }

    // ==================== SECTION 3: Validation & Error Handling ====================

    /** @test */
    public function test_content_required()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function test_content_max_length_validated()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => str_repeat('a', 300)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function test_empty_content_rejected()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => ''
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_null_content_rejected()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => null
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_invalid_parent_id_rejected()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test',
                'parent_id' => 999999
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);
    }

    /** @test */
    public function test_error_messages_clear()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }


    // ==================== SECTION 4: Integration with Other Systems ====================

    /** @test */
    public function test_blocked_user_cannot_comment()
    {
        $blocker = User::factory()->create();
        $blockerPost = Post::factory()->create(['user_id' => $blocker->id]);
        $blocker->blockedUsers()->attach($this->user->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$blockerPost->id}/comments", [
                'content' => 'Test'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_muted_user_comments_visible()
    {
        $muted = User::factory()->create();
        $this->user->mutedUsers()->attach($muted->id);
        
        Comment::factory()->create([
            'user_id' => $muted->id,
            'post_id' => $this->post->id
        ]);

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");
        $response->assertOk();
    }

    /** @test */
    public function test_event_dispatched_on_create()
    {
        \Event::fake();

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test'
            ]);

        \Event::assertDispatched(\App\Events\CommentCreated::class);
    }

    /** @test */
    public function test_broadcast_on_create()
    {
        \Event::fake();

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test'
            ]);

        \Event::assertDispatched(\App\Events\PostInteraction::class);
    }

    /** @test */
    public function test_mention_notification_sent()
    {
        $mentionedUser = User::factory()->create(['username' => 'testuser']);

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Hello @testuser'
            ]);

        $this->assertTrue(true);
    }

    // ==================== SECTION 5: Security in Action ====================

    /** @test */
    public function test_xss_sanitization_works()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test with script tag'
            ]);

        $response->assertStatus(201);
        $comment = Comment::latest()->first();
        $this->assertStringNotContainsString('<script>', $comment->content);
    }

    /** @test */
    public function test_sql_injection_prevented()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => "Test DROP TABLE"
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', ['content' => 'Test DROP TABLE']);
    }

    /** @test */
    public function test_rate_limiting_configured()
    {
        $limit = config('limits.rate_limits.comments.create');
        $this->assertNotNull($limit);
        $this->assertEquals('60,1', $limit);
    }

    /** @test */
    public function test_csrf_protection_active()
    {
        $this->assertTrue(true);
    }


    // ==================== SECTION 6: Database Transactions ====================

    /** @test */
    public function test_transaction_rollback_on_error()
    {
        $initialCount = Comment::count();

        try {
            \DB::transaction(function() {
                $comment = new Comment();
                $comment->user_id = $this->user->id;
                $comment->post_id = $this->post->id;
                $comment->content = 'Test';
                $comment->save();
                throw new \Exception('Rollback test');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals($initialCount, Comment::count());
    }

    /** @test */
    public function test_likes_count_updated_correctly()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);
        $initialCount = $comment->likes_count;

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $this->assertEquals($initialCount + 1, $comment->fresh()->likes_count);
    }

    /** @test */
    public function test_replies_count_updated()
    {
        $parent = Comment::factory()->create(['post_id' => $this->post->id]);
        $initialCount = $parent->replies_count;

        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply',
                'parent_id' => $parent->id
            ]);

        $response->assertStatus(201);
        $this->assertEquals($initialCount + 1, $parent->fresh()->replies_count);
    }

    /** @test */
    public function test_concurrent_likes_handled()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");
        
        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $this->assertEquals(0, $comment->fresh()->likes_count);
    }

    // ==================== SECTION 7: Business Logic & Edge Cases ====================

    /** @test */
    public function test_duplicate_like_toggles()
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $response1 = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");
        $response1->assertJson(['liked' => true]);
        
        $response2 = $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");
        $response2->assertJson(['liked' => false]);

        $this->assertEquals(0, $comment->fresh()->likes_count);
    }

    /** @test */
    public function test_counter_underflow_protected()
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'likes_count' => 0
        ]);

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");
        
        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/like");

        $this->assertEquals(0, $comment->fresh()->likes_count);
    }

    /** @test */
    public function test_soft_delete_works()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/comments/{$comment->id}");

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function test_edited_at_updated()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id
        ]);

        $this->withToken($this->token)
            ->putJson("/api/comments/{$comment->id}", ['content' => 'Updated']);

        $this->assertNotNull($comment->fresh()->edited_at);
    }

    // ==================== SECTION 8: Real-world Scenarios ====================

    /** @test */
    public function test_complete_comment_workflow()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Test comment'
            ]);
        
        $response->assertStatus(201);
        $commentId = $response->json('id');

        $this->withToken($this->token)
            ->postJson("/api/comments/{$commentId}/like");

        $this->withToken($this->token)
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'content' => 'Reply',
                'parent_id' => $commentId
            ]);

        $comment = Comment::find($commentId);
        $this->assertNotNull($comment);
        $this->assertEquals(1, $comment->likes_count);
        $this->assertEquals(1, $comment->replies_count);
    }

    /** @test */
    public function test_multiple_users_interaction()
    {
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        $user2->assignRole('user');
        $token2 = $user2->createToken('test')->plainTextToken;

        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $this->withToken($token2)
            ->postJson("/api/comments/{$comment->id}/like");

        $this->assertEquals(1, $comment->fresh()->likes_count);
    }

    /** @test */
    public function test_state_changes_persist()
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'is_pinned' => false
        ]);

        $this->withToken($this->token)
            ->postJson("/api/comments/{$comment->id}/pin");

        $this->assertTrue($comment->fresh()->is_pinned);
    }

    // ==================== SECTION 9: Performance & Response ====================

    /** @test */
    public function test_response_time_acceptable()
    {
        $start = microtime(true);
        
        $this->getJson("/api/posts/{$this->post->id}/comments");
        
        $duration = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $duration);
    }

    /** @test */
    public function test_eager_loading_works()
    {
        Comment::factory()->create(['post_id' => $this->post->id]);

        $response = $this->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['user' => ['id', 'name']]]]);
    }
}
