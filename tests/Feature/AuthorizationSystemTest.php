<?php

/**
 * Authorization System Test Script
 * Run: php artisan test:authorization
 */

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorizationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
    }

    /** @test */
    public function test_roles_are_created()
    {
        $this->assertEquals(5, Role::count());
        $this->assertTrue(Role::where('name', 'user')->exists());
        $this->assertTrue(Role::where('name', 'verified')->exists());
        $this->assertTrue(Role::where('name', 'premium')->exists());
        $this->assertTrue(Role::where('name', 'moderator')->exists());
        $this->assertTrue(Role::where('name', 'admin')->exists());
    }

    /** @test */
    public function test_permissions_are_created()
    {
        $this->assertEquals(37, Permission::count());
        $this->assertTrue(Permission::where('name', 'post.create')->exists());
        $this->assertTrue(Permission::where('name', 'admin.panel.access')->exists());
    }

    /** @test */
    public function test_user_role_has_correct_permissions()
    {
        $role = Role::findByName('user');
        $this->assertTrue($role->hasPermissionTo('post.create'));
        $this->assertTrue($role->hasPermissionTo('comment.create'));
        $this->assertFalse($role->hasPermissionTo('user.ban'));
    }

    /** @test */
    public function test_admin_role_has_all_permissions()
    {
        $role = Role::findByName('admin');
        $this->assertEquals(37, $role->permissions->count());
    }

    /** @test */
    public function test_user_can_be_assigned_role()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $this->assertTrue($user->hasRole('user'));
        $this->assertTrue($user->can('post.create'));
    }

    /** @test */
    public function test_post_policy_view_public_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['is_private' => false]);
        
        $this->assertTrue($user->can('view', $post));
    }

    /** @test */
    public function test_post_policy_view_private_post_by_follower()
    {
        $owner = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();
        $follower->following()->attach($owner->id);
        
        $post = Post::factory()->create(['user_id' => $owner->id, 'is_private' => true]);
        
        $this->assertTrue($follower->can('view', $post));
    }

    /** @test */
    public function test_post_policy_cannot_view_private_post_by_non_follower()
    {
        $owner = User::factory()->create(['is_private' => true]);
        $user = User::factory()->create();
        
        $post = Post::factory()->create(['user_id' => $owner->id, 'is_private' => true]);
        
        $this->assertFalse($user->can('view', $post));
    }

    /** @test */
    public function test_post_policy_owner_can_update()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        $this->assertTrue($user->can('update', $post));
    }

    /** @test */
    public function test_post_policy_non_owner_cannot_update()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);
        
        $this->assertFalse($other->can('update', $post));
    }

    /** @test */
    public function test_post_policy_admin_can_delete_any()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $post = Post::factory()->create();
        
        $this->assertTrue($admin->can('delete', $post));
    }

    /** @test */
    public function test_comment_policy_verified_user_can_create()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        
        $this->assertTrue($user->can('create', \App\Models\Comment::class));
    }

    /** @test */
    public function test_profile_policy_can_view_public_profile()
    {
        $user = User::factory()->create();
        $profile = User::factory()->create(['is_private' => false]);
        
        $this->assertTrue($user->can('view', $profile));
    }

    /** @test */
    public function test_profile_policy_cannot_view_blocked_profile()
    {
        $user = User::factory()->create();
        $blocker = User::factory()->create(['blocked_users' => [$user->id]]);
        
        $this->assertFalse($user->can('view', $blocker));
    }

    /** @test */
    public function test_message_policy_can_send_to_followers_only()
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create([
            'notification_preferences' => ['dm_settings' => 'followers']
        ]);
        
        // Not following
        $this->assertFalse($sender->can('send', [$recipient, \App\Models\Message::class]));
        
        // Following
        $recipient->following()->attach($sender->id);
        $this->assertTrue($sender->can('send', [$recipient, \App\Models\Message::class]));
    }

    /** @test */
    public function test_scheduled_post_policy_premium_can_create()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('premium');
        
        $this->assertTrue($user->can('create', \App\Models\ScheduledPost::class));
    }

    /** @test */
    public function test_community_policy_moderator_can_moderate()
    {
        $user = User::factory()->create();
        $community = \App\Models\Community::factory()->create();
        
        // Assign moderator role in community
        $community->members()->attach($user->id, ['role' => 'moderator']);
        
        $this->assertTrue($user->can('moderate', $community));
    }

    /** @test */
    public function test_report_policy_moderator_can_review()
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');
        
        $this->assertTrue($moderator->can('viewAny', \App\Models\Report::class));
    }

    /** @test */
    public function test_middleware_blocks_unauthorized_permission()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $response = $this->actingAs($user)
            ->getJson('/api/admin/users');
        
        $response->assertStatus(403);
    }

    /** @test */
    public function test_middleware_allows_authorized_permission()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $response = $this->actingAs($admin)
            ->getJson('/api/admin/users');
        
        $response->assertStatus(200);
    }
}
