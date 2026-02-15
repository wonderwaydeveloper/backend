<?php

namespace Tests\Feature;

use App\Models\{ABTest, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ABTestTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_admin_can_create_ab_test(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/ab-tests', [
            'name' => 'homepage_redesign',
            'description' => 'Testing new homepage design',
            'variants' => [
                'A' => ['color' => 'blue'],
                'B' => ['color' => 'green'],
            ],
            'traffic_percentage' => 50,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'data']);
        
        $this->assertDatabaseHas('ab_tests', [
            'name' => 'homepage_redesign',
            'status' => 'draft',
        ]);
    }

    public function test_user_cannot_create_ab_test(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/ab-tests', [
            'name' => 'test',
            'variants' => ['A' => [], 'B' => []],
        ]);

        $response->assertForbidden();
    }

    public function test_admin_can_list_ab_tests(): void
    {
        ABTest::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->getJson('/api/ab-tests');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_start_ab_test(): void
    {
        $test = ABTest::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin)->postJson("/api/ab-tests/{$test->id}/start");

        $response->assertOk();
        $this->assertEquals('active', $test->fresh()->status);
    }

    public function test_admin_can_stop_ab_test(): void
    {
        $test = ABTest::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->admin)->postJson("/api/ab-tests/{$test->id}/stop");

        $response->assertOk();
        $this->assertEquals('completed', $test->fresh()->status);
    }

    public function test_user_can_be_assigned_to_test(): void
    {
        $test = ABTest::factory()->create([
            'name' => 'button_color',
            'status' => 'active',
            'starts_at' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/ab-tests/assign', [
            'test_name' => 'button_color',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['variant', 'in_test']);
    }

    public function test_user_gets_same_variant_on_multiple_assignments(): void
    {
        $test = ABTest::factory()->create([
            'name' => 'feature_test',
            'status' => 'active',
            'starts_at' => now()->subHour(),
        ]);

        $response1 = $this->actingAs($this->user)->postJson('/api/ab-tests/assign', [
            'test_name' => 'feature_test',
        ]);

        $response2 = $this->actingAs($this->user)->postJson('/api/ab-tests/assign', [
            'test_name' => 'feature_test',
        ]);

        $this->assertEquals(
            $response1->json('variant'),
            $response2->json('variant')
        );
    }

    public function test_user_can_track_event(): void
    {
        $test = ABTest::factory()->create([
            'name' => 'checkout_flow',
            'status' => 'active',
            'starts_at' => now()->subHour(),
        ]);

        $this->actingAs($this->user)->postJson('/api/ab-tests/assign', [
            'test_name' => 'checkout_flow',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/ab-tests/track', [
            'test_name' => 'checkout_flow',
            'event_type' => 'conversion',
            'event_data' => ['amount' => 99.99],
        ]);

        $response->assertOk()
            ->assertJson(['tracked' => true]);

        $this->assertDatabaseHas('ab_test_events', [
            'ab_test_id' => $test->id,
            'user_id' => $this->user->id,
            'event_type' => 'conversion',
        ]);
    }

    public function test_admin_can_view_test_results(): void
    {
        $test = ABTest::factory()->create();

        $response = $this->actingAs($this->admin)->getJson("/api/ab-tests/{$test->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'test',
                'participants',
                'results',
                'conversion_rates',
                'statistical_significance',
            ]);
    }

    public function test_validation_requires_minimum_two_variants(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/ab-tests', [
            'name' => 'invalid_test',
            'variants' => ['A' => []],
        ]);

        $response->assertUnprocessable();
    }

    public function test_validation_limits_maximum_four_variants(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/ab-tests', [
            'name' => 'too_many_variants',
            'variants' => [
                'A' => [],
                'B' => [],
                'C' => [],
                'D' => [],
                'E' => [],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/ab-tests');
        $response->assertUnauthorized();
    }
}
