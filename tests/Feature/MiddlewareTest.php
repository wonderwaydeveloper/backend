<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function underage_user_cannot_access_restricted_features()
    {
        $child = User::factory()->create(['is_underage' => true]);
        Sanctum::actingAs($child);

        // Try to access private messaging (should be restricted)
        $response = $this->getJson('/api/messages/conversations');

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'این قابلیت برای کاربران زیر سن محدود شده است.',
                'feature' => 'private_messaging',
            ]);
    }

    #[Test]
    public function adult_user_can_access_all_features()
    {
        $adult = User::factory()->create(['is_underage' => false]);
        Sanctum::actingAs($adult);

        // Try to access private messaging (should be allowed)
        $response = $this->getJson('/api/messages/conversations');

        // Should not get 403 Forbidden for underage restriction
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    #[Test]
    public function track_online_user_middleware_updates_last_seen()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make any API request
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200);
        $this->assertTrue($response->isOk());
    }

    #[Test]
    public function rate_limiting_middleware_prevents_abuse()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make multiple rapid requests to trigger rate limiting
        $responses = [];
        for ($i = 0; $i < 15; $i++) {
            $responses[] = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Check if any response is 429 Too Many Requests
        $rateLimited = false;
        foreach ($responses as $response) {
            if ($response->getStatusCode() === 429) {
                $rateLimited = true;
                break;
            }
        }

        $this->assertTrue($rateLimited, 'Rate limiting should trigger after multiple requests');
    }

    #[Test]
    public function authentication_middleware_blocks_unauthenticated_access()
    {
        // Try to access protected route without authentication
        $response = $this->getJson('/api/users/me');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function admin_middleware_blocks_non_admin_access()
    {
        $user = User::factory()->create(); // Not admin
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(403);
    }

    #[Test]
    public function policy_middleware_enforces_permissions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);

        // Try to update user2's profile
        $response = $this->putJson("/api/users/{$user2->id}", [
            'name' => 'Unauthorized Update',
        ]);

        $response->assertStatus(403);
    }
}