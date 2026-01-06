<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UnifiedAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_login_via_unified_controller()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['user', 'token']);
    }

    public function test_it_can_complete_multi_step_registration()
    {
        // Step 1
        $response = $this->postJson('/api/auth/register/step1', [
            'name' => 'Test User',
            'date_of_birth' => '1990-01-01',
            'contact' => 'test@example.com',
            'contact_type' => 'email'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure(['session_id', 'message', 'next_step']);

        $sessionId = $response->json('session_id');
        $session = Cache::get("registration:{$sessionId}");

        // Step 2
        $response = $this->postJson('/api/auth/register/step2', [
            'session_id' => $sessionId,
            'code' => $session['code']
        ]);

        $response->assertStatus(200)
                ->assertJson(['next_step' => 3]);

        // Step 3
        $response = $this->postJson('/api/auth/register/step3', [
            'session_id' => $sessionId,
            'username' => 'testuser',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure(['user', 'token']);
    }

    public function test_it_can_logout_via_unified_controller()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Logout successful']);
    }
}