<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1990-01-01'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(200)
                ->assertJson([
                    'meta' => [
                        'message' => 'User registered successfully'
                    ]
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'username',
                            'birth_date'
                        ],
                        'access_token',
                        'token_type'
                    ]
                ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'username' => 'johndoe'
        ]);
    }

    /** @test */
    public function user_cannot_register_with_existing_email()
    {
        User::factory()->create([
            'email' => 'existing@example.com',
            'username' => 'existinguser'
        ]);

        $userData = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1990-01-01'
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(200)
                ->assertJson([
                    'meta' => [
                        'status' => 422,
                        'message' => 'Validation failed'
                    ]
                ])
                ->assertJsonFragment([
                    'email' => ['The email has already been taken.']
                ]);
    }

    /** @test */
    public function user_cannot_register_with_missing_required_fields()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
            // username و birth_date حذف شدند
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(200)
                ->assertJson([
                    'meta' => [
                        'status' => 422,
                        'message' => 'Validation failed'
                    ]
                ])
                ->assertJsonFragment([
                    'username' => ['The username field is required.'],
                    'birth_date' => ['The birth date field is required.']
                ]);
    }

    /** @test */
    public function user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser',
            'password' => bcrypt('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJson([
                    'meta' => [
                        'message' => 'Login successful'
                    ]
                ])
                ->assertJsonStructure([
                    'data' => [
                        'user' => [
                            'id',
                            'name',
                            'username'
                        ],
                        'access_token',
                        'token_type'
                    ]
                ]);
    }

    /** @test */
    public function user_cannot_login_with_wrong_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'testuser', 
            'password' => bcrypt('correct_password')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'meta' => [
                        'status' => 401,
                        'message' => 'Invalid credentials'
                    ]
                ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_email()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'meta' => [
                        'status' => 401, 
                        'message' => 'Invalid credentials'
                    ]
                ]);
    }
}