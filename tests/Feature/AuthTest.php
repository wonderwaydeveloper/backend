<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmailVerification;
use App\Models\PhoneVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

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
                        'id',
                        'name',
                        'username',
                        'email',
                    ],
                    'access_token',
                ],
                'meta' => ['message'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe',
        ]);
    }

    /** @test */
    public function registration_requires_valid_data()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => '',
            'username' => 'invalid username',
            'email' => 'invalid-email',
            'password' => 'short',
            'birth_date' => '2020-01-01', // Underage
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'username', 'email', 'password', 'birth_date']);
    }

    /** @test */
    public function user_can_login_with_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                ],
            ]);
    }

    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function user_can_logout()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);

        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function user_can_request_email_verification()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/email/send-verification', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification email sent successfully']);

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'test@example.com',
            'type' => 'verification',
        ]);
    }

    /** @test */
    public function user_can_verify_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $verification = EmailVerification::create([
            'email' => 'test@example.com',
            'code' => '123456',
            'token' => 'test-token',
            'type' => 'verification',
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->postJson('/api/auth/email/verify', [
            'email' => 'test@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['verified' => true]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    /** @test */
    public function user_can_request_password_reset()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/password/forgot', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Password reset email sent successfully']);

        $this->assertDatabaseHas('email_verifications', [
            'email' => 'test@example.com',
            'type' => 'password_reset',
        ]);
    }

    /** @test */
    public function authenticated_user_can_get_current_user_info()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'followers_count',
                        'following_count',
                    ],
                    'access_token',
                ],
            ]);
    }

    /** @test */
    public function phone_verification_can_be_sent()
    {
        $response = $this->postJson('/api/auth/phone/send-verification', [
            'phone' => '+989123456789',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['token', 'expires_at'],
                'meta' => ['message'],
            ]);

        $this->assertDatabaseHas('phone_verifications', [
            'phone' => '+989123456789',
        ]);
    }

    /** @test */
    public function two_factor_authentication_can_be_enabled()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/two-factor/enable');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'enabled',
                    'qr_code',
                    'recovery_codes',
                ],
            ]);

        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    /** @test */
    public function two_factor_authentication_can_be_disabled()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'test-secret',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/two-factor/disable');

        $response->assertStatus(200)
            ->assertJson(['data' => ['enabled' => false]]);

        $this->assertFalse($user->fresh()->two_factor_enabled);
        $this->assertNull($user->fresh()->two_factor_secret);
    }
}