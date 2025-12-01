<?php

namespace Tests\Feature;

use App\Models\ParentalControl;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentalControlTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function parent_can_add_parental_control_for_child()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create([
            'birth_date' => now()->subYears(10)->format('Y-m-d'),
            'is_underage' => true,
        ]);
        
        $token = $parent->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/parental-controls', [
            'child_id' => $child->id,
            'restrictions' => [
                'max_daily_usage' => 60,
                'content_filter' => true,
                'block_explicit_content' => true,
            ],
            'allowed_features' => ['posts', 'comments'],
            'daily_limit_start' => '08:00',
            'daily_limit_end' => '20:00',
            'max_daily_usage' => 120,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.parent_id', $parent->id)
            ->assertJsonPath('data.child_id', $child->id)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('parental_controls', [
            'parent_id' => $parent->id,
            'child_id' => $child->id,
        ]);

        $this->assertEquals($parent->id, $child->fresh()->parent_id);
    }

    /** @test */
    public function cannot_add_parental_control_for_non_underage_user()
    {
        $parent = User::factory()->create();
        $adult = User::factory()->create([
            'birth_date' => now()->subYears(20)->format('Y-m-d'),
            'is_underage' => false,
        ]);
        
        $token = $parent->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/parental-controls', [
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
            'is_active' => true,
        ]);
        
        $token = $parent->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/parental-controls/{$child->id}", [
            'max_daily_usage' => 90,
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.max_daily_usage', 90)
            ->assertJsonPath('data.is_active', false);
    }

    /** @test */
    public function parent_can_view_parental_controls()
    {
        $parent = User::factory()->create();
        $child1 = User::factory()->create(['is_underage' => true]);
        $child2 = User::factory()->create(['is_underage' => true]);
        
        ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child1->id,
        ]);
        
        ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child2->id,
        ]);
        
        $token = $parent->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/parental-controls');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function parent_can_get_usage_report_for_child()
    {
        $parent = User::factory()->create();
        $child = User::factory()->create(['is_underage' => true]);
        
        ParentalControl::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
        ]);
        
        $token = $parent->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/parental-controls/{$child->id}/usage-report");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'child',
                    'period',
                    'usage_stats',
                    'activity_summary',
                    'restrictions'
                ]
            ]);
    }

    /** @test */
    public function child_with_time_restrictions_cannot_access_during_restricted_hours()
    {
        $child = User::factory()->create(['is_underage' => true]);
        
        // تنظیم محدودیت زمانی 8 صبح تا 8 شب
        ParentalControl::create([
            'parent_id' => User::factory()->create()->id,
            'child_id' => $child->id,
            'daily_limit_start' => '08:00:00',
            'daily_limit_end' => '20:00:00',
            'is_active' => true,
        ]);
        
        // شبیه‌سازی زمان خارج از محدوده مجاز
        $this->travelTo(now()->setTime(21, 0)); // ساعت 9 شب
        
        $token = $child->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/messages/conversations', [
            'user_ids' => [User::factory()->create()->id],
            'type' => 'direct',
        ]);

        $response->assertStatus(403);
        
        $this->travelBack(); // بازگشت به زمان فعلی
    }
}