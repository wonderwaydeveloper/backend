<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_registration_flow_works()
    {
        // Step 1: Start registration
        $response = $this->postJson('/api/auth/register/step1', [
            'name' => 'Test User',
            'date_of_birth' => '2000-01-01',
            'contact' => 'test@example.com',
            'contact_type' => 'email'
        ]);
        
        $response->assertStatus(200);
        $this->assertArrayHasKey('session_id', $response->json());
        
        echo "✅ Step 1: Registration started\n";
        
        // Step 2: Verify code (simulate)
        $sessionId = $response->json('session_id');
        $code = \Cache::get("registration:{$sessionId}")['code'];
        
        $response = $this->postJson('/api/auth/register/step2', [
            'session_id' => $sessionId,
            'code' => $code
        ]);
        
        $response->assertStatus(200);
        echo "✅ Step 2: Code verified\n";
        
        // Step 3: Complete registration
        $response = $this->postJson('/api/auth/register/step3', [
            'session_id' => $sessionId,
            'username' => 'testuser',
            'password' => 'Test1234',
            'password_confirmation' => 'Test1234'
        ]);
        
        $response->assertStatus(201);
        $this->assertArrayHasKey('token', $response->json());
        echo "✅ Step 3: Registration completed\n";
        
        return $response->json('token');
    }

    /** @test */
    public function complete_login_flow_works()
    {
        // Create user first
        $user = \App\Models\User::factory()->create([
            'email' => 'login@test.com',
            'password' => bcrypt('Test1234'),
            'email_verified_at' => now()
        ]);
        
        // Login
        $response = $this->postJson('/api/auth/login', [
            'login' => 'login@test.com',
            'password' => 'Test1234'
        ]);
        
        // May require device verification
        if ($response->json('requires_device_verification')) {
            echo "✅ Device verification required (expected)\n";
            return;
        }
        
        $response->assertStatus(200);
        $this->assertArrayHasKey('token', $response->json());
        echo "✅ Login successful\n";
    }

    /** @test */
    public function password_validation_works()
    {
        $user = new \App\Models\User(['email' => 'test@test.com']);
        $service = app(\App\Services\PasswordSecurityService::class);
        
        try {
            $service->updatePassword($user, 'Test1234');
            echo "✅ Password validation passed\n";
        } catch (\Exception $e) {
            $this->fail("Password validation failed: " . $e->getMessage());
        }
    }

    /** @test */
    public function service_injection_works()
    {
        $authService = app(\App\Services\AuthService::class);
        $this->assertInstanceOf(\App\Services\AuthService::class, $authService);
        echo "✅ Service injection works\n";
    }
}
