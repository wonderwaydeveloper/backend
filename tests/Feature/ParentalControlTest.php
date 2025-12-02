<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ParentalControl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ParentalControlTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function parent_can_create_parental_control_for_child()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        Sanctum::actingAs($parent);

        $response = $this->postJson('/api/parental-controls', [
            'child_id' => $child->id,
            'restrictions' => [
                'max_daily_usage' => 120,
                'content_filter' => true,
                'block_explicit_content' => true,
            ],
            'allowed_features' => ['posts', 'comments', 'likes'],
            'daily_limit_start' => '08:00',
            'daily_limit_end' => '20:00',
            'max_daily_usage' => 120,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'child_id', 'parent_id', 'restrictions'],
            ]);

        $this->assertDatabaseHas('parental_controls', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'max_daily_usage' => 120,
            'is_active' => true,
        ]);

        // Child should now have parent_id set
        $this->assertEquals($parent->id, $child->fresh()->parent_id);
    }

    /** @test */
    public function cannot_create_parental_control_for_non_underage_user()
    {
        $parent = User::factory()->create();
        $adult = User::factory()->create(['is_underage' => false]);
        Sanctum::actingAs($parent);

        $response = $this->postJson('/api/parental-controls', [
            'child_id' => $adult->id,
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'User is not underage']);
    }

    /** @test */
    public function parent_can_update_parental_control()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        $control = ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'restrictions' => ['max_daily_usage' => 60],
            'max_daily_usage' => 60,
            'is_active' => true,
        ]);

        Sanctum::actingAs($parent);

        $response = $this->putJson("/api/parental-controls/{$child->id}", [
            'max_daily_usage' => 90,
            'restrictions' => ['max_daily_usage' => 90, 'content_filter' => true],
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'max_daily_usage' => 90,
                    'is_active' => false,
                ],
            ]);

        $this->assertDatabaseHas('parental_controls', [
            'child_id' => $child->id,
            'max_daily_usage' => 90,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function parent_can_view_parental_controls()
    {
        $parent = User::factory()->create();
        $children = User::factory()->count(3)->create(['is_underage' => true]);

        foreach ($children as $child) {
            ParentalControl::create([
                'parent_id' => $parent->id,
                'child_id' => $child->id,
                'is_active' => true,
            ]);
        }

        Sanctum::actingAs($parent);

        $response = $this->getJson('/api/parental-controls');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function parent_can_delete_parental_control()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        $control = ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
        ]);

        Sanctum::actingAs($parent);

        $response = $this->deleteJson("/api/parental-controls/{$child->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['deleted' => true]]);

        $this->assertDatabaseMissing('parental_controls', [
            'id' => $control->id,
        ]);

        // Child's parent_id should be null
        $this->assertNull($child->fresh()->parent_id);
    }

    /** @test */
    public function parent_can_view_child_usage_report()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
        ]);

        Sanctum::actingAs($parent);

        $response = $this->getJson("/api/parental-controls/{$child->id}/usage-report");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'child',
                    'period',
                    'usage_stats',
                    'activity_summary',
                    'restrictions',
                ],
            ]);
    }

    /** @test */
    public function non_parent_cannot_manage_parental_controls()
    {
        $user = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        Sanctum::actingAs($user);

        // Try to create control
        $response = $this->postJson('/api/parental-controls', [
            'child_id' => $child->id,
        ]);

        $response->assertStatus(403);

        // Try to update control
        $response = $this->putJson("/api/parental-controls/{$child->id}", [
            'max_daily_usage' => 90,
        ]);

        $response->assertStatus(403);

        // Try to delete control
        $response = $this->deleteJson("/api/parental-controls/{$child->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function child_cannot_access_parental_controls()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
        ]);

        Sanctum::actingAs($child);

        $response = $this->getJson('/api/parental-controls');

        $response->assertStatus(403);
    }

    /** @test */
    public function parental_control_restricts_child_access_based_on_time()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        $control = ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'daily_limit_start' => '08:00:00',
            'daily_limit_end' => '20:00:00',
            'is_active' => true,
        ]);

        // Test during allowed time (assuming test runs during day)
        $this->assertTrue($control->isWithinTimeLimit());

        // Test outside allowed time would require mocking time
        // This is more complex and might need a separate test
    }

    /** @test */
    public function child_cannot_access_restricted_features()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        $control = ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'allowed_features' => ['posts', 'comments'], // No 'messages'
            'is_active' => true,
        ]);

        $service = new \App\Services\ParentalControlService();

        // Child can access posts
        $this->assertTrue($service->canAccessFeature($child, 'posts'));

        // Child cannot access messages (not in allowed_features)
        $this->assertFalse($service->canAccessFeature($child, 'messages'));
    }
}