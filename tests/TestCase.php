<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Disable exception handling to see actual errors
        // $this->withoutExceptionHandling();
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createPost(array $attributes = []): \App\Models\Post
    {
        return \App\Models\Post::factory()->create($attributes);
    }

    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?: $this->createUser();
        $token = $user->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    protected function actingAsAdmin(): self
    {
        $admin = $this->createUser(['username' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }
}