<?php

namespace Tests\Feature;

use App\Models\PhoneVerificationCode;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhoneAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable rate limiting for tests
        $this->withoutMiddleware([
            \App\Http\Middleware\AdvancedRateLimit::class,
        ]);
    }

    public function test_can_send_verification_code()
    {
        $this->mock(SmsService::class, function ($mock) {
            $mock->shouldReceive('sendVerificationCode')
                ->once()
                ->with('+1234567890', \Mockery::type('string'));
        });

        $response = $this->postJson('/api/auth/phone/send-code', [
            'phone' => '+1234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification code sent successfully']);

        $this->assertDatabaseHas('phone_verification_codes', [
            'phone' => '+1234567890',
            'verified' => false,
        ]);
    }

    public function test_can_verify_phone_code()
    {
        PhoneVerificationCode::create([
            'phone' => '+1234567890',
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'verified' => false,
        ]);

        $response = $this->postJson('/api/auth/phone/verify', [
            'phone' => '+1234567890',
            'verification_code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Phone verified successfully', 'verified' => true]);

        $this->assertDatabaseHas('phone_verification_codes', [
            'phone' => '+1234567890',
            'code' => '123456',
            'verified' => true,
        ]);
    }

    public function test_can_register_with_verified_phone()
    {
        PhoneVerificationCode::create([
            'phone' => '+1234567890',
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'verified' => true,
        ]);

        $response = $this->postJson('/api/auth/phone/register', [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', [
            'phone' => '+1234567890',
            'username' => 'johndoe',
        ]);
    }

    public function test_can_login_with_phone()
    {
        $user = User::factory()->create([
            'phone' => '+1234567890',
        ]);

        PhoneVerificationCode::create([
            'phone' => '+1234567890',
            'code' => '123456',
            'expires_at' => now()->addMinutes(5),
            'verified' => false, // Should be false for fresh login
        ]);

        $response = $this->postJson('/api/auth/phone/login', [
            'phone' => '+1234567890',
            'verification_code' => '123456',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    public function test_cannot_verify_expired_code()
    {
        PhoneVerificationCode::create([
            'phone' => '+1234567890',
            'code' => '123456',
            'expires_at' => now()->subMinutes(10),
            'verified' => false,
        ]);

        $response = $this->postJson('/api/auth/phone/verify', [
            'phone' => '+1234567890',
            'verification_code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_cannot_register_without_verified_phone()
    {
        $response = $this->postJson('/api/auth/phone/register', [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'date_of_birth' => '1990-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }
}