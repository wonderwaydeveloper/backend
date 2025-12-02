<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function brute_force_login_protection()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Attempt multiple failed logins
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

            if ($response->getStatusCode() === 429) {
                // Rate limited
                break;
            }
        }

        // Should eventually get rate limited
        $this->assertEquals(429, $response->getStatusCode());
    }

    /** @test */
    public function sql_injection_protection()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Attempt SQL injection in search
        $response = $this->getJson('/api/users/search?query=\' OR \'1\'=\'1');

        // Should not crash or expose data
        $response->assertStatus(200);
        
        // The query should be sanitized, might return empty results
        // but shouldn't throw SQL errors
    }

    /** @test */
    public function xss_protection()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $xssPayload = '<script>alert("xss")</script>';

        // Try to post content with XSS
        $response = $this->postJson('/api/posts', [
            'content' => $xssPayload,
        ]);

        $response->assertStatus(201);

        // Retrieve the post
        $postId = $response->json('data.id');
        $getResponse = $this->getJson("/api/posts/{$postId}");

        $getResponse->assertStatus(200);
        
        // The response should have sanitized the content
        // (Laravel typically escapes HTML in JSON responses)
        $content = $getResponse->json('data.content');
        
        // The script tags should be escaped or removed
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringNotContainsString('</script>', $content);
    }

    /** @test */
    public function sensitive_data_exposure()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);

        // Try to access user2's sensitive data
        $response = $this->getJson("/api/users/{$user2->id}");

        $response->assertStatus(200);

        // Should not include sensitive fields like email, phone for other users
        $data = $response->json('data');
        
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('phone', $data);
        $this->assertArrayNotHasKey('two_factor_enabled', $data);
        $this->assertArrayNotHasKey('last_login_at', $data);

        // Should include public fields
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('bio', $data);
    }

    /** @test */
    public function session_fixation_protection()
    {
        // Create user and login
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.access_token');

        // Logout
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);
        $logoutResponse = $this->postJson('/api/auth/logout');
        $logoutResponse->assertStatus(200);

        // Try to use old token after logout
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/auth/user');

        $response->assertStatus(401); // Should be unauthorized
    }

    /** @test */
    public function password_policy_enforcement()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => '123', // Too short
            'password_confirmation' => '123',
            'birth_date' => '1990-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Try with weak but valid password
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password', // Weak but meets length requirement
            'password_confirmation' => 'password',
            'birth_date' => '1990-01-01',
        ]);

        // Should succeed (policy might be basic)
        $response->assertStatus(200);
    }

    /** @test */
    public function csrf_protection_for_state_changing_operations()
    {
        // Note: For API routes with Sanctum, CSRF is typically disabled
        // but we can verify that the API doesn't rely on session-based CSRF
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Make a state-changing request without CSRF token (should work for API)
        $response = $this->postJson('/api/posts', [
            'content' => 'Test post',
        ]);

        $response->assertStatus(201); // Should succeed without CSRF token
    }
}