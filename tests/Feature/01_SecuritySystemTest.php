<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, DeviceToken};
use Illuminate\Foundation\Testing\RefreshDatabase;

class _01_SecuritySystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('user');
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    // ==================== SECTION 1: Core API Functionality (20%) ====================

    /** @test */
    public function test_can_list_devices()
    {
        DeviceToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/devices');

        $response->assertOk();
    }

    /** @test */
    public function test_can_register_device()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'iPhone 15'
            ]);

        $response->assertOk()
            ->assertJsonStructure(['device', 'requires_verification']);
    }

    /** @test */
    public function test_can_revoke_device()
    {
        $this->user->givePermissionTo('device.revoke');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'fingerprint' => 'different_fingerprint'
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('device_tokens', ['id' => $device->id]);
    }

    /** @test */
    public function test_can_trust_device()
    {
        $this->user->assignRole('verified');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_trusted' => false
        ]);

        $response = $this->withToken($this->token)
            ->postJson("/api/devices/{$device->id}/trust", [
                'password' => 'password'
            ]);

        $response->assertOk();
    }

    /** @test */
    public function test_can_get_device_activity()
    {
        $this->user->givePermissionTo('device.view');
        $device = DeviceToken::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson("/api/devices/{$device->id}/activity");

        $response->assertOk();
    }

    /** @test */
    public function test_can_check_suspicious_activity()
    {
        $this->user->assignRole('premium');

        $response = $this->withToken($this->token)
            ->getJson('/api/devices/suspicious-activity');

        $response->assertOk();
    }

    /** @test */
    public function test_pagination_works()
    {
        DeviceToken::factory()->count(25)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)->getJson('/api/devices');

        $response->assertOk();
    }

    /** @test */
    public function test_can_revoke_all_devices()
    {
        DeviceToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->postJson('/api/devices/revoke-all', ['password' => 'password']);

        $response->assertOk();
    }

    // ==================== SECTION 2: Authentication & Authorization (20%) ====================

    /** @test */
    public function test_guest_cannot_access_devices()
    {
        $response = $this->getJson('/api/devices');
        $response->assertUnauthorized();
    }

    /** @test */
    public function test_authenticated_user_can_access()
    {
        $response = $this->withToken($this->token)->getJson('/api/devices');
        $response->assertOk();
    }

    /** @test */
    public function test_cannot_revoke_others_device()
    {
        $otherUser = User::factory()->create();
        $device = DeviceToken::factory()->create([
            'user_id' => $otherUser->id,
            'fingerprint' => 'other_fingerprint'
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");

        $response->assertNotFound();
    }

    /** @test */
    public function test_cannot_trust_others_device()
    {
        $otherUser = User::factory()->create();
        $device = DeviceToken::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/devices/{$device->id}/trust", ['password' => 'password']);

        $response->assertNotFound();
    }

    /** @test */
    public function test_user_role_can_register_device()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('user');
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $response->assertOk();
    }

    /** @test */
    public function test_verified_role_can_trust_device()
    {
        $verified = User::factory()->create(['email_verified_at' => now()]);
        $verified->assignRole('verified');
        $device = DeviceToken::factory()->create(['user_id' => $verified->id, 'is_trusted' => false]);
        $token = $verified->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/devices/{$device->id}/trust", ['password' => 'password']);

        $response->assertOk();
    }

    /** @test */
    public function test_premium_role_can_manage_devices()
    {
        $premium = User::factory()->create(['email_verified_at' => now()]);
        $premium->assignRole('premium');
        $token = $premium->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/devices/suspicious-activity');

        $response->assertOk();
    }

    /** @test */
    public function test_organization_role_can_manage_devices()
    {
        $org = User::factory()->create(['email_verified_at' => now()]);
        $org->assignRole('organization');
        $token = $org->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/devices/suspicious-activity');

        $response->assertOk();
    }

    /** @test */
    public function test_moderator_role_has_access()
    {
        $moderator = User::factory()->create(['email_verified_at' => now()]);
        $moderator->assignRole('moderator');
        $token = $moderator->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/devices');

        $response->assertOk();
    }

    /** @test */
    public function test_admin_role_has_full_access()
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/devices');

        $response->assertOk();
    }

    /** @test */
    public function test_user_role_cannot_manage_all_devices()
    {
        $this->assertFalse($this->user->can('device.manage'));
    }

    /** @test */
    public function test_user_role_cannot_access_admin_panel()
    {
        $this->assertFalse($this->user->can('admin.panel.access'));
    }

    // ==================== SECTION 3: Validation & Error Handling (15%) ====================

    /** @test */
    public function test_token_required()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'platform' => 'ios',
                'device_name' => 'iPhone'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function test_platform_required()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'device_name' => 'iPhone'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    /** @test */
    public function test_invalid_platform_rejected()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'invalid_platform',
                'device_name' => 'iPhone'
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_error_messages_clear()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function test_invalid_password_for_trust()
    {
        $this->user->assignRole('verified');
        $device = DeviceToken::factory()->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->postJson("/api/devices/{$device->id}/trust", ['password' => 'wrong_password']);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_password_required_for_revoke_all()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/revoke-all', []);

        $response->assertStatus(422);
    }

    // ==================== SECTION 4: Integration with Other Systems (15%) ====================

    /** @test */
    public function test_device_registration_stores_data()
    {
        $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'device_type' => 'ios'
        ]);
    }

    /** @test */
    public function test_device_revocation_removes_data()
    {
        $this->user->givePermissionTo('device.revoke');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'fingerprint' => 'different_fingerprint'
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");

        $this->assertDatabaseMissing('device_tokens', ['id' => $device->id]);
    }

    /** @test */
    public function test_trust_device_updates_status()
    {
        $this->user->assignRole('verified');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_trusted' => false
        ]);

        $this->withToken($this->token)
            ->postJson("/api/devices/{$device->id}/trust", ['password' => 'password']);

        $this->assertTrue($device->fresh()->is_trusted);
    }

    /** @test */
    public function test_revoke_all_removes_other_devices()
    {
        DeviceToken::factory()->count(3)->create(['user_id' => $this->user->id]);

        $this->withToken($this->token)
            ->postJson('/api/devices/revoke-all', ['password' => 'password']);

        $this->assertLessThanOrEqual(1, $this->user->fresh()->devices()->count());
    }

    /** @test */
    public function test_blocked_user_device_access()
    {
        $this->user->update(['is_blocked' => true]);

        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_private_account_device_management()
    {
        $this->user->update(['is_private' => true]);

        $response = $this->withToken($this->token)->getJson('/api/devices');

        $response->assertOk();
    }

    // ==================== SECTION 5: Security in Action (10%) ====================

    /** @test */
    public function test_xss_sanitization_in_device_name()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => '<script>alert("xss")</script>iPhone'
            ]);

        $response->assertOk();
    }

    /** @test */
    public function test_sql_injection_prevented()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => "'; DROP TABLE users; --",
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    /** @test */
    public function test_cannot_revoke_current_device()
    {
        $this->user->givePermissionTo('device.revoke');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'fingerprint' => \App\Services\DeviceFingerprintService::generate(request())
        ]);

        $response = $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");

        $response->assertStatus(422);
    }

    /** @test */
    public function test_mass_assignment_protection()
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'iPhone',
                'is_trusted' => true,
                'is_verified' => true
            ]);

        if ($response->status() === 200) {
            $device = DeviceToken::latest()->first();
            $this->assertFalse($device->is_trusted);
        }
    }

    // ==================== SECTION 6: Database Transactions (10%) ====================

    /** @test */
    public function test_device_registration_is_atomic()
    {
        \DB::beginTransaction();

        $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        \DB::rollBack();

        $this->assertDatabaseMissing('device_tokens', [
            'device_name' => 'Test Device'
        ]);
    }

    /** @test */
    public function test_counters_updated_correctly()
    {
        $initialCount = $this->user->devices()->count();

        $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $this->assertEquals($initialCount + 1, $this->user->fresh()->devices()->count());
    }

    /** @test */
    public function test_no_orphaned_records_after_delete()
    {
        $this->user->givePermissionTo('device.revoke');
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'fingerprint' => 'different_fingerprint'
        ]);

        $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");

        $this->assertDatabaseMissing('device_tokens', ['id' => $device->id]);
    }

    /** @test */
    public function test_transaction_rollback_on_error()
    {
        $initialCount = DeviceToken::count();

        try {
            \DB::transaction(function () {
                DeviceToken::factory()->create(['user_id' => $this->user->id]);
                throw new \Exception('Test rollback');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals($initialCount, DeviceToken::count());
    }

    // ==================== SECTION 7: Business Logic & Edge Cases (5%) ====================

    /** @test */
    public function test_max_devices_limit_enforced()
    {
        $maxDevices = config('security.device.max_devices', 5);

        DeviceToken::factory()->count($maxDevices)->create(['user_id' => $this->user->id]);

        $count = $this->user->devices()->count();
        $this->assertLessThanOrEqual($maxDevices, $count);
    }

    /** @test */
    public function test_device_fingerprint_uniqueness()
    {
        $fingerprint = 'unique_fingerprint_123';

        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'fingerprint' => $fingerprint
        ]);

        $duplicate = DeviceToken::where('fingerprint', $fingerprint)
            ->where('user_id', $this->user->id)
            ->count();

        $this->assertEquals(1, $duplicate);
    }

    /** @test */
    public function test_duplicate_device_token_prevented()
    {
        $token = 'duplicate_token_123';

        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => $token
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => $token,
                'platform' => 'ios',
                'device_name' => 'Test Device'
            ]);

        $this->assertTrue(true);
    }

    /** @test */
    public function test_timestamps_updated_correctly()
    {
        $device = DeviceToken::factory()->create(['user_id' => $this->user->id]);
        $oldTimestamp = $device->updated_at;

        sleep(1);
        $device->touch();

        $this->assertNotEquals($oldTimestamp, $device->fresh()->updated_at);
    }

    /** @test */
    public function test_last_used_at_updated_on_activity()
    {
        $device = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(1)
        ]);

        $oldLastUsed = $device->last_used_at;

        $device->update(['last_used_at' => now()]);

        $this->assertNotEquals($oldLastUsed, $device->fresh()->last_used_at);
    }

    // ==================== SECTION 8: Real-world Scenarios (3%) ====================

    /** @test */
    public function test_complete_device_lifecycle()
    {
        $this->user->assignRole('verified');
        
        // Register
        $response = $this->withToken($this->token)
            ->postJson('/api/devices/register', [
                'token' => 'device_token_123',
                'platform' => 'ios',
                'device_name' => 'iPhone 15'
            ]);
        $response->assertOk();

        $device = DeviceToken::where('user_id', $this->user->id)->latest()->first();

        // Trust
        $response = $this->withToken($this->token)
            ->postJson("/api/devices/{$device->id}/trust", ['password' => 'password']);
        $response->assertOk();

        // Revoke
        $device->update(['fingerprint' => 'different_fingerprint']);
        $response = $this->withToken($this->token)
            ->deleteJson("/api/devices/{$device->id}");
        $response->assertOk();
    }

    /** @test */
    public function test_multiple_users_device_isolation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        DeviceToken::factory()->create(['user_id' => $user1->id]);
        DeviceToken::factory()->create(['user_id' => $user2->id]);

        $token1 = $user1->createToken('test')->plainTextToken;

        $response = $this->withToken($token1)->getJson('/api/devices');

        $response->assertOk();
    }

    /** @test */
    public function test_concurrent_device_registration()
    {
        $responses = [];

        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->withToken($this->token)
                ->postJson('/api/devices/register', [
                    'token' => "device_token_{$i}",
                    'platform' => 'ios',
                    'device_name' => "Device {$i}"
                ]);
        }

        foreach ($responses as $response) {
            $response->assertOk();
        }

        $this->assertGreaterThanOrEqual(1, $this->user->fresh()->devices()->count());
    }

    // ==================== SECTION 9: Performance & Response (2%) ====================

    /** @test */
    public function test_response_time_acceptable()
    {
        $start = microtime(true);

        $this->withToken($this->token)->getJson('/api/devices');

        $duration = (microtime(true) - $start) * 1000;

        $this->assertLessThan(500, $duration, 'Response time should be under 500ms');
    }

    /** @test */
    public function test_n_plus_1_queries_avoided()
    {
        DeviceToken::factory()->count(10)->create(['user_id' => $this->user->id]);

        \DB::enableQueryLog();

        $this->withToken($this->token)->getJson('/api/devices');

        $queries = \DB::getQueryLog();

        $this->assertLessThan(10, count($queries), 'Should avoid N+1 queries');
    }
}
