<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PhoneVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_email()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1990-01-01',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id', 'name', 'username', 'email'
                    ],
                    'access_token'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function registration_requires_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'username']);
    }

    /** @test */
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email'],
                    'access_token'
                ]
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    /** @test */
    public function user_can_request_password_reset()
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset email sent successfully']);
    }

    /** @test */
    public function phone_verification_can_be_sent()
    {
        $response = $this->postJson('/api/auth/phone/send-verification', [
            'phone' => '+989123456789',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['token', 'expires_at']]);

        $this->assertDatabaseHas('phone_verifications', [
            'phone' => '+989123456789'
        ]);
    }

    /** @test */
    public function banned_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'banned@example.com',
            'password' => Hash::make('password123'),
            'is_banned' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'banned@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Account is banned']);
    }
}