<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_device()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/devices/register', [
                'token' => 'test_device_token_123',
                'platform' => 'ios',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Device registered successfully']);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'test_device_token_123',
            'device_type' => 'ios',
        ]);
    }

    public function test_user_can_unregister_device()
    {
        $user = User::factory()->create();
        $token = 'test_device_token_123';

        DeviceToken::create([
            'user_id' => $user->id,
            'token' => $token,
            'device_type' => 'ios',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/devices/{$token}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Device unregistered successfully']);

        $this->assertDatabaseMissing('device_tokens', [
            'user_id' => $user->id,
            'token' => $token,
        ]);
    }

    public function test_device_registration_requires_valid_platform()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/devices/register', [
                'token' => 'test_token',
                'platform' => 'invalid_platform',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_guest_cannot_register_device()
    {
        $response = $this->postJson('/api/devices/register', [
            'token' => 'test_token',
            'platform' => 'ios',
        ]);

        $response->assertStatus(401);
    }
}