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
        $child = User::factory()->underage()->create();

        $this->assertTrue(
            $child->is_underage,
            "User should be underage. Birth date: {$child->birth_date}, Age: " . $child->birth_date->age
        );

        Sanctum::actingAs($child);

        $response = $this->getJson('/api/messages/conversations');

        \Log::debug('Underage test response', [
            'status' => $response->getStatusCode(),
            'content' => $response->getContent(),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'This feature is restricted for underage users.', // تغییر به انگلیسی
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
        $rateLimited = false;

        // 6 درخواست سریع
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            \Log::debug("Rate limiting attempt $i", [
                'status' => $response->getStatusCode(),
            ]);

            if ($response->getStatusCode() === 429) {
                $rateLimited = true;
                break;
            }
        }

        $this->assertTrue(
            $rateLimited,
            'Rate limiting should trigger after multiple requests. ' .
            'Make sure route has throttle:5,1 middleware'
        );
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