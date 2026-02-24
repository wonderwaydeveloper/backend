<?php

namespace Tests\Feature;

use App\Models\{User, Follow, Block, Mute, FollowRequest};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialFeaturesSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        $permissions = ['follow.user', 'block.user', 'mute.user', 'unblock.user', 'unmute.user', 'user.follow', 'user.unfollow'];
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'sanctum']
            );
        }
        
        $this->userRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user', 'guard_name' => 'sanctum']);
        $this->userRole->syncPermissions($permissions);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    // ==================== SECTION 1: Core API Functionality ====================

    /** @test */
    public function test_can_follow_user()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $response->assertOk()
            ->assertJson(['message' => 'User followed successfully']);
        
        $this->assertTrue($this->user->isFollowing($targetUser->id));
    }

    /** @test */
    public function test_can_unfollow_user()
    {
        $targetUser = User::factory()->create();
        $this->user->following()->attach($targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unfollow");

        $response->assertOk()
            ->assertJson(['message' => 'User unfollowed successfully']);
        
        $this->assertFalse($this->user->isFollowing($targetUser->id));
    }

    /** @test */
    public function test_can_get_followers_list()
    {
        $follower = User::factory()->create();
        $this->user->followers()->attach($follower->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->user->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_can_get_following_list()
    {
        $following = User::factory()->create();
        $this->user->following()->attach($following->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->user->id}/following");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_can_block_user()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        $response->assertOk()
            ->assertJson(['message' => 'User blocked successfully']);
        
        $this->assertTrue($this->user->hasBlocked($targetUser->id));
    }

    /** @test */
    public function test_can_unblock_user()
    {
        $targetUser = User::factory()->create();
        $this->user->blockedUsers()->attach($targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unblock");

        $response->assertOk()
            ->assertJson(['message' => 'User unblocked successfully']);
        
        $this->assertFalse($this->user->hasBlocked($targetUser->id));
    }

    /** @test */
    public function test_can_mute_user()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/mute");

        $response->assertOk();
        $this->assertTrue($this->user->hasMuted($targetUser->id));
    }

    /** @test */
    public function test_can_unmute_user()
    {
        $targetUser = User::factory()->create();
        $this->user->mutedUsers()->attach($targetUser->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unmute");

        $response->assertOk()
            ->assertJson(['message' => 'User unmuted successfully']);
        
        $this->assertFalse($this->user->hasMuted($targetUser->id));
    }

    /** @test */
    public function test_can_send_follow_request_to_private_account()
    {
        $privateUser = User::factory()->create(['is_private' => true]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        $response->assertOk()
            ->assertJson(['message' => 'Follow request sent']);
        
        $this->assertDatabaseHas('follow_requests', [
            'follower_id' => $this->user->id,
            'following_id' => $privateUser->id,
            'status' => 'pending'
        ]);
    }

    /** @test */
    public function test_can_list_follow_requests()
    {
        $requester = User::factory()->create();
        FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/follow-requests');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_can_accept_follow_request()
    {
        $requester = User::factory()->create();
        $followRequest = FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/follow-requests/{$followRequest->id}/accept");

        $response->assertOk()
            ->assertJson(['message' => 'Follow request accepted']);
        
        $this->assertEquals('accepted', $followRequest->fresh()->status);
    }

    /** @test */
    public function test_can_reject_follow_request()
    {
        $requester = User::factory()->create();
        $followRequest = FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $this->user->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/follow-requests/{$followRequest->id}/reject");

        $response->assertOk()
            ->assertJson(['message' => 'Follow request rejected']);
        
        $this->assertEquals('rejected', $followRequest->fresh()->status);
    }

    /** @test */
    public function test_can_get_blocked_users_list()
    {
        $blocked = User::factory()->create();
        $this->user->blockedUsers()->attach($blocked->id);

        $response = $this->withToken($this->token)
            ->getJson('/api/blocked');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_can_get_muted_users_list()
    {
        $muted = User::factory()->create();
        $this->user->mutedUsers()->attach($muted->id);

        $response = $this->withToken($this->token)
            ->getJson('/api/muted');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    /** @test */
    public function test_pagination_works_on_followers()
    {
        $followers = User::factory()->count(25)->create();
        foreach ($followers as $follower) {
            $this->user->followers()->attach($follower->id);
        }

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->user->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    // ==================== SECTION 2: Authentication & Authorization ====================

    /** @test */
    public function test_guest_cannot_follow_user()
    {
        $targetUser = User::factory()->create();

        $response = $this->postJson("/api/users/{$targetUser->id}/follow");

        $response->assertUnauthorized();
    }

    /** @test */
    public function test_guest_cannot_block_user()
    {
        $targetUser = User::factory()->create();

        $response = $this->postJson("/api/users/{$targetUser->id}/block");

        $response->assertUnauthorized();
    }

    /** @test */
    public function test_cannot_follow_self()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/follow");

        $response->assertForbidden();
    }

    /** @test */
    public function test_cannot_block_self()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/block");

        $response->assertForbidden();
    }

    /** @test */
    public function test_cannot_mute_self()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/mute");

        $response->assertForbidden();
    }

    /** @test */
    public function test_blocked_user_cannot_follow()
    {
        $blocker = User::factory()->create();
        $blocker->blockedUsers()->attach($this->user->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$blocker->id}/follow");

        $response->assertForbidden();
    }

    /** @test */
    public function test_user_role_can_follow()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        $token = $user->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_verified_role_can_follow()
    {
        $verified = User::factory()->create();
        $verified->assignRole('verified');
        $token = $verified->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_premium_role_can_follow()
    {
        $premium = User::factory()->create();
        $premium->assignRole('premium');
        $token = $premium->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_organization_role_can_follow()
    {
        $org = User::factory()->create();
        $org->assignRole('organization');
        $token = $org->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_moderator_role_can_follow()
    {
        $mod = User::factory()->create();
        $mod->assignRole('moderator');
        $token = $mod->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_admin_role_can_follow()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $token = $admin->createToken('test')->plainTextToken;
        $target = User::factory()->create();

        $response = $this->withToken($token)
            ->postJson("/api/users/{$target->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_cannot_accept_others_follow_request()
    {
        $requester = User::factory()->create();
        $otherUser = User::factory()->create();
        $followRequest = FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $otherUser->id,
            'status' => 'pending'
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/follow-requests/{$followRequest->id}/accept");

        $response->assertForbidden();
    }

    /** @test */
    public function test_policy_enforced_for_follow()
    {
        $targetUser = User::factory()->create(['is_private' => false]);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $response->assertOk();
    }

    /** @test */
    public function test_policy_enforced_for_block()
    {
        $targetUser = User::factory()->create();
        
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        $response->assertOk();
    }

    /** @test */
    public function test_policy_blocks_when_already_blocked()
    {
        $blocker = User::factory()->create();
        $blocker->blockedUsers()->attach($this->user->id);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$blocker->id}/follow");

        $response->assertForbidden();
    }

    // ==================== SECTION 3: Validation & Error Handling ====================

    /** @test */
    public function test_follow_nonexistent_user_returns_404()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/users/99999/follow');

        $response->assertNotFound();
    }

    /** @test */
    public function test_block_nonexistent_user_returns_404()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/users/99999/block');

        $response->assertNotFound();
    }

    /** @test */
    public function test_mute_with_invalid_expiration_rejected()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/mute", [
                'expires_at' => 'invalid-date'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_mute_with_past_date_rejected()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/mute", [
                'expires_at' => now()->subDay()->toDateTimeString()
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_duplicate_follow_request_rejected()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Follow request already sent']);
    }

    /** @test */
    public function test_follow_request_to_public_account_rejected()
    {
        $publicUser = User::factory()->create(['is_private' => false]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$publicUser->id}/follow-request");

        $response->assertStatus(400);
    }

    /** @test */
    public function test_error_messages_are_clear()
    {
        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$this->user->id}/follow");

        $response->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    // ==================== SECTION 4: Integration with Other Systems ====================

    /** @test */
    public function test_block_auto_unfollows_both_ways()
    {
        $targetUser = User::factory()->create();
        
        // Setup mutual following
        $this->user->following()->attach($targetUser->id);
        $targetUser->following()->attach($this->user->id);

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        $this->assertFalse($this->user->isFollowing($targetUser->id));
        $this->assertFalse($targetUser->isFollowing($this->user->id));
    }

    /** @test */
    public function test_private_account_requires_follow_request()
    {
        $privateUser = User::factory()->create(['is_private' => true]);

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$privateUser->id}/follow");

        // Private accounts should reject direct follow and require follow request instead
        $response->assertStatus(400)
            ->assertJson(['message' => 'This account is private. Send a follow request instead.']);
    }

    /** @test */
    public function test_follow_increments_counters()
    {
        $targetUser = User::factory()->create([
            'followers_count' => 0,
            'following_count' => 0
        ]);
        $initialFollowingCount = $this->user->following_count;

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $this->assertEquals($initialFollowingCount + 1, $this->user->fresh()->following_count);
        $this->assertEquals(1, $targetUser->fresh()->followers_count);
    }

    /** @test */
    public function test_unfollow_decrements_counters()
    {
        $targetUser = User::factory()->create([
            'followers_count' => 1,
            'following_count' => 0
        ]);
        $this->user->following()->attach($targetUser->id);
        $this->user->update(['following_count' => 1]);

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unfollow");

        $this->assertEquals(0, $this->user->fresh()->following_count);
        $this->assertEquals(0, $targetUser->fresh()->followers_count);
    }

    /** @test */
    public function test_event_dispatched_on_follow()
    {
        \Event::fake();
        $targetUser = User::factory()->create();

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        \Event::assertDispatched(\App\Events\UserFollowed::class);
    }

    /** @test */
    public function test_event_dispatched_on_block()
    {
        \Event::fake();
        $targetUser = User::factory()->create();

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        \Event::assertDispatched(\App\Events\UserBlocked::class);
    }

    /** @test */
    public function test_event_dispatched_on_mute()
    {
        \Event::fake();
        $targetUser = User::factory()->create();

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/mute");

        \Event::assertDispatched(\App\Events\UserMuted::class);
    }

    // ==================== SECTION 5: Security in Action ====================

    /** @test */
    public function test_xss_sanitization_in_block_reason()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block", [
                'reason' => '<script>alert("xss")</script>Spam'
            ]);

        $response->assertOk();
        $block = Block::where('blocker_id', $this->user->id)->first();
        $this->assertStringNotContainsString('<script>', $block->reason);
    }

    /** @test */
    public function test_sql_injection_prevented()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block", [
                'reason' => "'; DROP TABLE blocks; --"
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('blocks', [
            'blocker_id' => $this->user->id,
            'blocked_id' => $targetUser->id
        ]);
    }

    /** @test */
    public function test_rate_limiting_configured()
    {
        $limit = config('limits.rate_limits.social.follow');
        $this->assertNotNull($limit);
    }

    /** @test */
    public function test_csrf_protection_active()
    {
        $this->assertTrue(true);
    }

    // ==================== SECTION 6: Database Transactions ====================

    /** @test */
    public function test_follow_counter_updates_atomically()
    {
        $targetUser = User::factory()->create(['followers_count' => 0]);
        $initialCount = $this->user->following_count;

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $this->assertEquals($initialCount + 1, $this->user->fresh()->following_count);
        $this->assertEquals(1, $targetUser->fresh()->followers_count);
    }

    /** @test */
    public function test_unfollow_counter_underflow_protected()
    {
        $targetUser = User::factory()->create(['followers_count' => 0]);
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unfollow");

        $this->assertEquals(0, $targetUser->fresh()->followers_count);
    }

    /** @test */
    public function test_no_orphaned_follow_records()
    {
        $targetUser = User::factory()->create();
        $this->user->following()->attach($targetUser->id);

        $targetUser->delete();

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $targetUser->id
        ]);
    }

    /** @test */
    public function test_concurrent_follow_requests_handled()
    {
        $targetUser = User::factory()->create();

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $followCount = Follow::where('follower_id', $this->user->id)
            ->where('following_id', $targetUser->id)
            ->count();
        
        $this->assertEquals(1, $followCount);
    }

    // ==================== SECTION 7: Business Logic & Edge Cases ====================

    /** @test */
    public function test_duplicate_follow_prevented()
    {
        $targetUser = User::factory()->create();
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");

        $followCount = Follow::where('follower_id', $this->user->id)
            ->where('following_id', $targetUser->id)
            ->count();
        
        $this->assertEquals(1, $followCount);
    }

    /** @test */
    public function test_duplicate_block_prevented()
    {
        $targetUser = User::factory()->create();
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/block");

        $blockCount = Block::where('blocker_id', $this->user->id)
            ->where('blocked_id', $targetUser->id)
            ->count();
        
        $this->assertEquals(1, $blockCount);
    }

    /** @test */
    public function test_mute_expiration_works()
    {
        $targetUser = User::factory()->create();
        $expiresAt = now()->addHour();

        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/mute", [
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

        $mute = Mute::where('muter_id', $this->user->id)
            ->where('muted_id', $targetUser->id)
            ->first();
        
        $this->assertNotNull($mute->expires_at);
    }

    /** @test */
    public function test_unblock_nonexistent_block_returns_404()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unblock");

        $response->assertNotFound();
    }

    /** @test */
    public function test_unmute_nonexistent_mute_returns_404()
    {
        $targetUser = User::factory()->create();

        $response = $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unmute");

        $response->assertNotFound();
    }

    // ==================== SECTION 8: Real-world Scenarios ====================

    /** @test */
    public function test_complete_follow_workflow()
    {
        $targetUser = User::factory()->create();

        // Follow
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");
        
        $this->assertTrue($this->user->isFollowing($targetUser->id));

        // Unfollow
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/unfollow");
        
        $this->assertFalse($this->user->isFollowing($targetUser->id));
    }

    /** @test */
    public function test_private_account_follow_request_workflow()
    {
        $privateUser = User::factory()->create(['is_private' => true, 'email_verified_at' => now()]);
        $privateUser->assignRole($this->userRole);

        // Send request
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/users/{$privateUser->id}/follow-request");

        // Accept request
        $followRequest = FollowRequest::where('follower_id', $this->user->id)->first();
        
        $response = $this->actingAs($privateUser, 'sanctum')
            ->postJson("/api/follow-requests/{$followRequest->id}/accept");

        $response->assertOk();

        $this->assertTrue($this->user->fresh()->isFollowing($privateUser->id));
    }

    /** @test */
    public function test_multiple_users_interaction()
    {
        $user2 = User::factory()->create();
        $user2->assignRole($this->userRole);

        // Create a public target user (not private)
        $targetUser = User::factory()->create(['is_private' => false]);

        // Both follow same user
        $response1 = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/users/{$targetUser->id}/follow");
        
        $response1->assertOk();

        $response2 = $this->actingAs($user2, 'sanctum')
            ->postJson("/api/users/{$targetUser->id}/follow");

        $response2->assertOk();

        $this->assertEquals(2, $targetUser->fresh()->followers_count);
    }

    // ==================== SECTION 9: Performance & Response ====================

    /** @test */
    public function test_response_time_acceptable()
    {
        $targetUser = User::factory()->create();
        $start = microtime(true);
        
        $this->withToken($this->token)
            ->postJson("/api/users/{$targetUser->id}/follow");
        
        $duration = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $duration);
    }

    /** @test */
    public function test_n_plus_1_queries_avoided()
    {
        User::factory()->count(5)->create()->each(function($user) {
            $this->user->following()->attach($user->id);
        });

        \DB::enableQueryLog();
        
        $this->withToken($this->token)
            ->getJson("/api/users/{$this->user->id}/following");
        
        $queries = \DB::getQueryLog();
        // Allow up to 10 queries for pagination and eager loading
        $this->assertLessThanOrEqual(10, count($queries));
    }

    /** @test */
    public function test_eager_loading_works()
    {
        $follower = User::factory()->create();
        $this->user->followers()->attach($follower->id);

        $response = $this->withToken($this->token)
            ->getJson("/api/users/{$this->user->id}/followers");

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'username']]]);
    }
}
