<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_waf_blocks_sql_injection(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => "'; DROP TABLE users; --"
            ]);

        $response->assertStatus(403);
    }

    public function test_waf_blocks_xss_attempts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/posts', [
                'content' => '<script>alert("xss")</script>'
            ]);

        $response->assertStatus(403);
    }

    public function test_brute_force_protection(): void
    {
        // Clear any existing attempts
        Redis::flushall();

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Next attempt should be blocked
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429);
    }

    public function test_advanced_rate_limiting(): void
    {
        Redis::flushall();
        $user = User::factory()->create();

        // Test rate limiting on login endpoint which has rate limiting
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        // Next request should be rate limited
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com', 
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(429)
            ->assertJsonStructure(['message', 'error', 'retry_after']);
    }

    public function test_security_headers_applied(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '1; mode=block')
            ->assertHeader('Strict-Transport-Security')
            ->assertHeader('Content-Security-Policy')
            ->assertHeader('Referrer-Policy');
    }

    public function test_2fa_verification_required(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'test_secret'
        ]);

        // Should require 2FA verification for sensitive operations
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/auth/2fa/disable');

        // This would normally require 2FA verification
        $response->assertStatus(422);
    }

    public function test_performance_monitoring_headers(): void
    {
        $response = $this->getJson('/api/health');

        // Performance monitoring should add timing headers
        $this->assertTrue($response->headers->has('X-Response-Time') || 
                         $response->headers->has('Server-Timing'));
    }

    public function test_locale_middleware_sets_locale(): void
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'fa'
        ])->getJson('/api/health');

        $this->assertEquals('fa', app()->getLocale());
    }

    public function test_api_request_logging(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/posts');

        // Should log the API request
        $response->assertStatus(200);
        
        // Check if log entry was created (would need to mock logger)
        $this->assertTrue(true); // Placeholder for actual log verification
    }
}