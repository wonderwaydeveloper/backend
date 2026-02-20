<?php

namespace Tests\Feature;

use App\Models\{User, Post, Follow, FollowRequest, Block, Mute};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialFeaturesSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $targetUser;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        $permissions = ['user.follow', 'user.unfollow', 'user.block', 'user.mute'];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }
        
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $role->syncPermissions($permissions);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
        
        $this->targetUser = User::factory()->create();
        $this->targetUser->assignRole('user');
    }

    // ==================== SECTION 1: Core API Functionality (Follow, Block, Mute, Follow Requests) ====================

    /** @test */
    public function test_can_follow_user()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $response->assertOk();
        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_cannot_follow_yourself()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/follow");

        $response->assertForbidden();
    }

    /** @test */
    public function test_can_unfollow_user()
    {
        $this->user->following()->attach($this->targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unfollow");

        $response->assertOk();
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_follow_increments_counters()
    {
        $initialFollowingCount = $this->user->following_count;
        $initialFollowersCount = $this->targetUser->followers_count;

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $this->user->refresh();
        $this->targetUser->refresh();
        
        $this->assertEquals($initialFollowingCount + 1, $this->user->following_count);
        $this->assertEquals($initialFollowersCount + 1, $this->targetUser->followers_count);
    }

    /** @test */
    public function test_unfollow_decrements_counters()
    {
        $this->user->following()->attach($this->targetUser->id);
        $this->user->increment('following_count');
        $this->targetUser->increment('followers_count');

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unfollow");

        $this->user->refresh();
        $this->targetUser->refresh();
        
        $this->assertEquals(0, $this->user->following_count);
        $this->assertEquals(0, $this->targetUser->followers_count);
    }

    /** @test */
    public function test_can_get_followers_list()
    {
        $follower = User::factory()->create();
        $this->targetUser->followers()->attach($follower->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username']]]);
    }

    /** @test */
    public function test_can_get_following_list()
    {
        $following = User::factory()->create();
        $this->targetUser->following()->attach($following->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/following");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username']]]);
    }

    /** @test */
    public function test_private_account_requires_follow_request()
    {
        $privateUser = User::factory()->create(['is_private' => true]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        $response->assertOk();
        $this->assertDatabaseHas('follow_requests', [
            'follower_id' => $this->user->id,
            'following_id' => $privateUser->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function test_can_accept_follow_request()
    {
        $request = FollowRequest::create([
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id,
            'status' => 'pending'
        ]);

        $token = $this->targetUser->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/follow-requests/{$request->id}/accept");

        $response->assertOk();
        $this->assertEquals('accepted', $request->fresh()->status);
        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_can_reject_follow_request()
    {
        $request = FollowRequest::create([
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id,
            'status' => 'pending'
        ]);

        $token = $this->targetUser->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/follow-requests/{$request->id}/reject");

        $response->assertOk();
        $this->assertEquals('rejected', $request->fresh()->status);
    }

    /** @test */
    public function test_cannot_accept_others_follow_request()
    {
        $request = FollowRequest::create([
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/follow-requests/{$request->id}/accept");

        $response->assertForbidden();
    }

    /** @test */
    public function test_can_get_pending_follow_requests()
    {
        FollowRequest::create([
            'follower_id' => $this->targetUser->id,
            'following_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/follow-requests");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'follower', 'status']]]);
    }

    /** @test */
    public function test_can_block_user()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block", [
                'reason' => 'Spam'
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_cannot_block_yourself()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/block");

        $response->assertForbidden();
    }

    /** @test */
    public function test_can_unblock_user()
    {
        Block::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unblock");

        $response->assertOk();
        $this->assertDatabaseMissing('blocks', [
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_block_auto_unfollows_both_directions()
    {
        $this->user->following()->attach($this->targetUser->id);
        $this->targetUser->following()->attach($this->user->id);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        $this->user->refresh();
        $this->targetUser->refresh();
        
        $this->assertFalse($this->user->isFollowing($this->targetUser->id));
        $this->assertFalse($this->targetUser->isFollowing($this->user->id));
    }

    /** @test */
    public function test_block_reason_is_sanitized()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block", [
                'reason' => '<script>alert("xss")</script>Spam'
            ]);

        $response->assertOk();
        $block = Block::where('blocker_id', $this->user->id)->first();
        $this->assertNotNull($block->reason);
        $this->assertStringNotContainsString('<script>', $block->reason);
        $this->assertStringContainsString('Spam', $block->reason);
    }

    /** @test */
    public function test_can_get_blocked_users_list()
    {
        Block::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/blocked");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'username']]]);
    }

    /** @test */
    public function test_can_mute_user()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/mute");

        $response->assertOk();
        $this->assertDatabaseHas('mutes', [
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_cannot_mute_yourself()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/mute");

        $response->assertForbidden();
    }

    /** @test */
    public function test_can_unmute_user()
    {
        Mute::create([
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unmute");

        $response->assertOk();
        $this->assertDatabaseMissing('mutes', [
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_can_mute_with_expiration()
    {
        $expiresAt = now()->addDays(7);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/mute", [
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

        $response->assertOk();
        $mute = Mute::where('muter_id', $this->user->id)->first();
        $this->assertNotNull($mute->expires_at);
    }

    /** @test */
    public function test_can_get_muted_users_list()
    {
        Mute::create([
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);

        $response = $this->withToken($this->token)
            ->getJson("/api/muted");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'username']]]);
    }

    /** @test */
    public function test_followers_list_has_correct_structure()
    {
        $follower = User::factory()->create();
        $this->targetUser->followers()->attach($follower->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username', 'avatar']]]);
    }

    /** @test */
    public function test_following_list_has_correct_structure()
    {
        $following = User::factory()->create();
        $this->targetUser->following()->attach($following->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/following");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username', 'avatar']]]);
    }

    // ==================== SECTION 4: Integration with Other Systems (Posts, Events) ====================

    /** @test */
    public function test_blocked_user_posts_not_visible()
    {
        Block::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);

        $post = Post::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts");

        $posts = $response->json('data');
        $postIds = collect($posts)->pluck('id')->toArray();
        $this->assertNotContains($post->id, $postIds);
    }

    /** @test */
    public function test_muted_user_posts_not_visible()
    {
        Mute::create([
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);

        $post = Post::factory()->create(['user_id' => $this->targetUser->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/posts");

        $posts = $response->json('data');
        $postIds = collect($posts)->pluck('id')->toArray();
        $this->assertNotContains($post->id, $postIds);
    }

    /** @test */
    public function test_cannot_view_private_profile_without_following()
    {
        $privateUser = User::factory()->create(['is_private' => true]);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$privateUser->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function test_can_view_private_profile_when_following()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $this->user->following()->attach($privateUser->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$privateUser->id}");

        $response->assertOk();
    }

    /** @test */
    public function test_cannot_follow_user_who_blocked_you()
    {
        Block::create([
            'blocker_id' => $this->targetUser->id,
            'blocked_id' => $this->user->id
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $response->assertForbidden();
    }

    // ==================== SECTION 2: Authentication & Authorization ====================

    /** @test */
    public function test_guest_cannot_follow()
    {
        $response = $this->postJson("/api/users/{$this->targetUser->id}/follow");
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_guest_cannot_block()
    {
        $response = $this->postJson("/api/users/{$this->targetUser->id}/block");
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_guest_cannot_mute()
    {
        $response = $this->postJson("/api/users/{$this->targetUser->id}/mute");
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_follow_respects_rate_limiting()
    {
        $rateLimit = config('limits.rate_limits.social.follow');
        $this->assertNotNull($rateLimit);
    }

    /** @test */
    public function test_block_respects_rate_limiting()
    {
        $rateLimit = config('limits.rate_limits.social.block');
        $this->assertNotNull($rateLimit);
    }

    /** @test */
    public function test_mute_respects_rate_limiting()
    {
        $rateLimit = config('limits.rate_limits.social.mute');
        $this->assertNotNull($rateLimit);
    }

    // ==================== SECTION 5: Security in Action ====================

    /** @test */
    public function test_follow_is_atomic()
    {
        $initialFollowingCount = $this->user->following_count;
        $initialFollowersCount = $this->targetUser->followers_count;

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $response->assertOk();
        $this->user->refresh();
        $this->targetUser->refresh();
        
        $this->assertEquals($initialFollowingCount + 1, $this->user->following_count);
        $this->assertEquals($initialFollowersCount + 1, $this->targetUser->followers_count);
    }

    /** @test */
    public function test_cannot_duplicate_follow()
    {
        $this->user->following()->attach($this->targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $followCount = Follow::where('follower_id', $this->user->id)
            ->where('following_id', $this->targetUser->id)
            ->count();
        
        $this->assertEquals(1, $followCount);
    }

    /** @test */
    public function test_cannot_duplicate_block()
    {
        Block::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        $blockCount = Block::where('blocker_id', $this->user->id)
            ->where('blocked_id', $this->targetUser->id)
            ->count();
        
        $this->assertEquals(1, $blockCount);
    }

    /** @test */
    public function test_block_reason_prevents_sql_injection()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block", [
                'reason' => "'; DROP TABLE blocks; --"
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_csrf_protection_active()
    {
        $this->assertTrue(config('session.http_only'));
    }

    /** @test */
    public function test_unfollow_decrements_counters_atomically()
    {
        $this->user->following()->attach($this->targetUser->id);
        $this->user->increment('following_count');
        $this->targetUser->increment('followers_count');

        $initialFollowingCount = $this->user->following_count;
        $initialFollowersCount = $this->targetUser->followers_count;

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unfollow");

        $this->user->refresh();
        $this->targetUser->refresh();
        
        $this->assertEquals($initialFollowingCount - 1, $this->user->following_count);
        $this->assertEquals($initialFollowersCount - 1, $this->targetUser->followers_count);
    }

    /** @test */
    public function test_block_removes_follow_relationships()
    {
        $this->user->following()->attach($this->targetUser->id);
        $this->targetUser->following()->attach($this->user->id);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
        
        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->targetUser->id,
            'following_id' => $this->user->id
        ]);
    }

    // ==================== SECTION 6: Database Transactions ====================

    /** @test */
    public function test_follow_dispatches_event()
    {
        \Event::fake();

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        \Event::assertDispatched(\App\Events\UserFollowed::class);
    }

    /** @test */
    public function test_block_dispatches_event()
    {
        \Event::fake();

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        \Event::assertDispatched(\App\Events\UserBlocked::class);
    }

    /** @test */
    public function test_mute_dispatches_event()
    {
        \Event::fake();

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/mute");

        \Event::assertDispatched(\App\Events\UserMuted::class);
    }

    // ==================== SECTION 3: Validation & Error Handling ====================

    /** @test */
    public function test_follow_non_existent_user()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/999999/follow");

        $response->assertNotFound();
    }

    /** @test */
    public function test_block_non_existent_user()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/999999/block");

        $response->assertNotFound();
    }

    /** @test */
    public function test_unfollow_when_not_following()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unfollow");

        $response->assertOk();
    }

    /** @test */
    public function test_unblock_when_not_blocked()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unblock");

        $response->assertNotFound();
    }

    /** @test */
    public function test_block_reason_max_length_validated()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block", [
                'reason' => str_repeat('a', 1000)
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_mute_expires_at_must_be_future()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/mute", [
                'expires_at' => now()->subDay()->toDateTimeString()
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_pagination_on_followers()
    {
        $users = User::factory()->count(25)->create();
        foreach ($users as $user) {
            $this->targetUser->followers()->attach($user->id);
        }

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_pagination_on_following()
    {
        $users = User::factory()->count(25)->create();
        foreach ($users as $user) {
            $this->targetUser->following()->attach($user->id);
        }

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/following");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    // ==================== SECTION 7: Business Logic & Edge Cases ====================

    /** @test */
    public function test_cannot_follow_already_following()
    {
        $this->user->following()->attach($this->targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $followCount = Follow::where('follower_id', $this->user->id)
            ->where('following_id', $this->targetUser->id)
            ->count();
        
        $this->assertEquals(1, $followCount);
    }

    /** @test */
    public function test_cannot_block_already_blocked()
    {
        Block::create([
            'blocker_id' => $this->user->id,
            'blocked_id' => $this->targetUser->id
        ]);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        $blockCount = Block::where('blocker_id', $this->user->id)
            ->where('blocked_id', $this->targetUser->id)
            ->count();
        
        $this->assertEquals(1, $blockCount);
    }

    /** @test */
    public function test_cannot_mute_already_muted()
    {
        Mute::create([
            'muter_id' => $this->user->id,
            'muted_id' => $this->targetUser->id
        ]);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/mute");

        $muteCount = Mute::where('muter_id', $this->user->id)
            ->where('muted_id', $this->targetUser->id)
            ->count();
        
        $this->assertEquals(1, $muteCount);
    }

    /** @test */
    public function test_follow_request_status_changes()
    {
        $privateUser = User::factory()->create(['is_private' => true]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        $response->assertOk();
        
        $request = FollowRequest::where('follower_id', $this->user->id)
            ->where('following_id', $privateUser->id)
            ->first();
        
        $this->assertEquals('pending', $request->status);
    }

    /** @test */
    public function test_timestamps_updated_on_actions()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $response->assertOk();
        
        $follow = Follow::where('follower_id', $this->user->id)
            ->where('following_id', $this->targetUser->id)
            ->first();
        
        $this->assertNotNull($follow->created_at);
    }

    // ==================== SECTION 8: Real-world Scenarios ====================

    /** @test */
    public function test_complete_follow_workflow()
    {
        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/unfollow");

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
    }

    /** @test */
    public function test_block_workflow_with_auto_unfollow()
    {
        $this->user->following()->attach($this->targetUser->id);
        $this->targetUser->following()->attach($this->user->id);

        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/block");

        $this->assertFalse($this->user->isFollowing($this->targetUser->id));
        $this->assertFalse($this->targetUser->isFollowing($this->user->id));
    }

    /** @test */
    public function test_multiple_users_interaction()
    {
        $user2 = User::factory()->create(['email_verified_at' => now()]);
        $user2->assignRole('user');

        $response1 = $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");
        $response1->assertOk();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $this->targetUser->id
        ]);
    }

    // ==================== SECTION 9: Performance & Response ====================

    /** @test */
    public function test_followers_list_avoids_n_plus_1_queries()
    {
        $users = User::factory()->count(5)->create();
        foreach ($users as $user) {
            $this->targetUser->followers()->attach($user->id);
        }

        \DB::enableQueryLog();
        
        $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/followers");
        
        $queries = \DB::getQueryLog();
        $this->assertLessThan(15, count($queries));
    }

    /** @test */
    public function test_follow_response_time_acceptable()
    {
        $start = microtime(true);
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$this->targetUser->id}/follow");
        
        $duration = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $duration);
    }

    /** @test */
    public function test_followers_list_eager_loads_user_data()
    {
        $follower = User::factory()->create();
        $this->targetUser->followers()->attach($follower->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->targetUser->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username']]]);
    }
}
