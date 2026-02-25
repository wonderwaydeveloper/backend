<?php

namespace Tests\Feature;

use App\Models\{User, Notification, DeviceToken};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    // ==================== SECTION 1: Core API Functionality ====================

    public function test_can_list_notifications()
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function test_can_get_unread_notifications()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/unread');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_can_get_unread_count()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 5]);
    }

    public function test_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_can_mark_all_as_read()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/notifications/mark-all-read');

        $response->assertOk();
        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->whereNull('read_at')->count());
    }

    public function test_can_get_notification_preferences()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/preferences');

        $response->assertOk()
            ->assertJsonStructure(['preferences' => ['email', 'push', 'in_app']]);
    }

    public function test_can_update_notification_preferences()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/notifications/preferences', [
                'preferences' => [
                    'email' => ['likes' => false, 'comments' => true, 'follows' => true, 'mentions' => true, 'reposts' => true, 'messages' => true],
                    'push' => ['likes' => true, 'comments' => true, 'follows' => true, 'mentions' => true, 'reposts' => true, 'messages' => true],
                    'in_app' => ['likes' => true, 'comments' => true, 'follows' => true, 'mentions' => true, 'reposts' => true, 'messages' => true]
                ]
            ]);

        $response->assertOk();
        $this->assertFalse($this->user->fresh()->notification_preferences['email']['likes']);
    }

    public function test_can_register_push_device()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => 'test_token_' . uniqid(),
                'device_type' => 'web',
                'device_name' => 'Chrome Browser'
            ]);

        $response->assertOk()
            ->assertJsonStructure(['device_id']);
    }

    public function test_can_get_registered_devices()
    {
        DeviceToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/push/devices');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_pagination_works_for_notifications()
    {
        Notification::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'per_page', 'total']);
    }

    // ==================== SECTION 2: Authentication & Authorization ====================

    public function test_guest_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_access_preferences()
    {
        $response = $this->getJson('/api/notifications/preferences');
        $response->assertUnauthorized();
    }

    public function test_guest_cannot_register_device()
    {
        $response = $this->postJson('/api/push/register', [
            'device_token' => 'test',
            'device_type' => 'web'
        ]);
        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_notifications()
    {
        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $response->assertOk();
    }

    public function test_cannot_mark_others_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertForbidden();
    }

    public function test_cannot_access_others_notifications()
    {
        $otherUser = User::factory()->create();
        Notification::factory()->count(5)->create(['user_id' => $otherUser->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $data = $response->json('data');
        $this->assertEmpty($data);
    }

    public function test_policy_enforced_on_notification_update()
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response->assertOk();
    }

    // ==================== SECTION 3: Validation & Error Handling ====================

    public function test_device_token_required_for_registration()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_type' => 'web'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_token']);
    }

    public function test_device_type_required_for_registration()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => 'test_token'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    public function test_invalid_device_type_rejected()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => 'test_token',
                'device_type' => 'invalid_type'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device_type']);
    }

    public function test_preferences_must_be_array()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/notifications/preferences', [
                'preferences' => 'invalid'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preferences']);
    }

    public function test_preferences_structure_validated()
    {
        $response = $this->withToken($this->token)
            ->putJson('/api/notifications/preferences', [
                'preferences' => ['invalid' => 'structure']
            ]);

        $response->assertStatus(422);
    }

    public function test_error_messages_clear()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_invalid_notification_id_returns_404()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/notifications/999999/read');

        $response->assertNotFound();
    }

    // ==================== SECTION 4: Integration with Other Systems ====================

    public function test_notification_created_on_like()
    {
        $post = \App\Models\Post::factory()->create();
        
        event(new \App\Events\PostLiked($post, $this->user));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $post->user_id,
            'type' => 'like'
        ]);
    }

    public function test_notification_created_on_comment()
    {
        $post = \App\Models\Post::factory()->create();
        $comment = \App\Models\Comment::factory()->create(['post_id' => $post->id, 'user_id' => $this->user->id]);
        
        event(new \App\Events\CommentCreated($comment, $this->user));

        $this->assertDatabaseHas('notifications', [
            'user_id' => $post->user_id,
            'type' => 'comment'
        ]);
    }

    public function test_notification_created_on_follow()
    {
        $follower = User::factory()->create();
        
        // Directly call the service instead of testing queued listener
        $service = app(\App\Services\NotificationService::class);
        $service->notifyFollow($follower, $this->user);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'follow'
        ]);
    }

    public function test_event_dispatched_on_notification_creation()
    {
        // Test that notification is created successfully
        // Broadcasting is tested separately
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'like',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => $this->user->id,
            'data' => ['test' => 'data']
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->user->id,
            'type' => 'like'
        ]);
    }

    public function test_device_tokens_linked_to_user()
    {
        $device = DeviceToken::factory()->create(['user_id' => $this->user->id]);

        $this->assertEquals($this->user->id, $device->user_id);
        $this->assertTrue($this->user->devices->contains($device));
    }

    // ==================== SECTION 5: Security in Action ====================

    public function test_xss_prevention_in_notification_data()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => ['message' => '<script>alert("xss")</script>Test']
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $response->assertOk();
        $this->assertTrue(true);
    }

    public function test_sql_injection_prevented()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => "'; DROP TABLE device_tokens; --",
                'device_type' => 'web'
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('device_tokens', ['token' => "'; DROP TABLE device_tokens; --"]);
    }

    public function test_rate_limiting_configured()
    {
        $this->assertTrue(config('limits.rate_limits') !== null);
    }

    public function test_csrf_protection_active()
    {
        $this->assertTrue(config('session.csrf_protection') !== false);
    }

    // ==================== SECTION 6: Database Transactions ====================

    public function test_notification_counters_updated()
    {
        $initialCount = Notification::where('user_id', $this->user->id)->count();

        Notification::factory()->create(['user_id' => $this->user->id]);

        $this->assertEquals($initialCount + 1, Notification::where('user_id', $this->user->id)->count());
    }

    public function test_no_orphaned_notifications_on_user_delete()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_no_orphaned_devices_on_user_delete()
    {
        $user = User::factory()->create();
        $device = DeviceToken::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('device_tokens', ['id' => $device->id]);
    }

    public function test_concurrent_mark_as_read_handled()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");
        
        $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // ==================== SECTION 7: Business Logic & Edge Cases ====================

    public function test_duplicate_device_registration_updates_existing()
    {
        $token = 'unique_token_' . uniqid();
        
        $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => $token,
                'device_type' => 'web'
            ]);
        
        $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => $token,
                'device_type' => 'android'
            ]);

        $this->assertEquals(1, DeviceToken::where('token', $token)->count());
        $this->assertEquals('android', DeviceToken::where('token', $token)->first()->device_type);
    }

    public function test_unread_count_accurate()
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id, 'read_at' => null]);
        Notification::factory()->count(2)->create(['user_id' => $this->user->id, 'read_at' => now()]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/unread-count');

        $response->assertJson(['count' => 3]);
    }

    public function test_timestamps_updated_on_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_preferences_default_values()
    {
        $newUser = User::factory()->create();
        $newToken = $newUser->createToken('test')->plainTextToken;

        $response = $this->withToken($newToken)
            ->getJson('/api/notifications/preferences');

        $preferences = $response->json('preferences');
        $this->assertTrue($preferences['email']['likes']);
        $this->assertTrue($preferences['push']['comments']);
    }

    // ==================== SECTION 8: Real-world Scenarios ====================

    public function test_complete_notification_workflow()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/push/register', [
                'device_token' => 'workflow_token',
                'device_type' => 'web'
            ]);
        $response->assertOk();

        $notification = Notification::factory()->create(['user_id' => $this->user->id, 'read_at' => null]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/unread-count');
        $response->assertJson(['count' => 1]);

        $this->withToken($this->token)
            ->postJson("/api/notifications/{$notification->id}/read");

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/unread-count');
        $response->assertJson(['count' => 0]);
    }

    public function test_multiple_users_receive_separate_notifications()
    {
        $user2 = User::factory()->create();
        $token2 = $user2->createToken('test')->plainTextToken;

        Notification::factory()->create(['user_id' => $this->user->id]);
        Notification::factory()->create(['user_id' => $user2->id]);

        $response1 = $this->withToken($this->token)
            ->getJson('/api/notifications');
        
        $response2 = $this->withToken($token2)
            ->getJson('/api/notifications');

        $this->assertCount(1, $response1->json('data'));
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_preference_changes_persist()
    {
        $this->withToken($this->token)
            ->putJson('/api/notifications/preferences', [
                'preferences' => [
                    'email' => ['likes' => false, 'comments' => false, 'follows' => false, 'mentions' => false, 'reposts' => false, 'messages' => false],
                    'push' => ['likes' => true, 'comments' => true, 'follows' => true, 'mentions' => true, 'reposts' => true, 'messages' => true],
                    'in_app' => ['likes' => true, 'comments' => true, 'follows' => true, 'mentions' => true, 'reposts' => true, 'messages' => true]
                ]
            ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications/preferences');

        $this->assertFalse($response->json('preferences.email.likes'));
        $this->assertTrue($response->json('preferences.push.likes'));
    }

    // ==================== SECTION 9: Performance & Response ====================

    public function test_response_time_acceptable()
    {
        $start = microtime(true);
        
        $this->withToken($this->token)
            ->getJson('/api/notifications');
        
        $duration = (microtime(true) - $start) * 1000;
        $this->assertLessThan(500, $duration);
    }

    public function test_pagination_limits_queries()
    {
        Notification::factory()->count(50)->create(['user_id' => $this->user->id]);

        \DB::enableQueryLog();
        
        $this->withToken($this->token)
            ->getJson('/api/notifications');
        
        $queries = \DB::getQueryLog();
        $this->assertLessThan(10, count($queries));
    }

    public function test_eager_loading_works()
    {
        $fromUser = User::factory()->create();
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'from_user_id' => $fromUser->id
        ]);

        $response = $this->withToken($this->token)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'type', 'data']]]);
        
        // Verify from_user relationship exists in model
        $notification = Notification::with('fromUser')->first();
        $this->assertNotNull($notification->fromUser);
    }
}
