<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_view_platform_stats()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'content',
                    'security',
                    'system'
                ]
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_admin_routes()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_ban_user()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create(['is_banned' => false]);
        
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/admin/users/{$user->id}/ban");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User banned successfully']);

        $this->assertTrue($user->fresh()->is_banned);
    }

    /** @test */
    public function admin_can_unban_user()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create(['is_banned' => true]);
        
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/admin/users/{$user->id}/unban");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User unbanned successfully']);

        $this->assertFalse($user->fresh()->is_banned);
    }

    /** @test */
    public function admin_can_view_underage_users()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        
        User::factory()->create(['is_underage' => true]);
        User::factory()->create(['is_underage' => true]);
        User::factory()->create(['is_underage' => false]);
        
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/admin/underage-users');

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.total_underage', 2);
    }

    /** @test */
    public function admin_can_toggle_phone_authentication()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        // First toggle
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/phone-auth/toggle');

        $response1->assertStatus(200)
            ->assertJson(['message' => 'Phone authentication enabled']);

        // Second toggle
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/admin/phone-auth/toggle');

        $response2->assertStatus(200)
            ->assertJson(['message' => 'Phone authentication disabled']);
    }

    /** @test */
    public function admin_can_update_platform_settings()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'max_posts_per_day', 'value' => '100'],
                ['key' => 'site_name', 'value' => 'New Site Name'],
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Settings updated successfully']);
    }
}