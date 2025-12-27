<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserSuggestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_user_suggestions()
    {
        $user = User::factory()->create();
        $suggestedUsers = User::factory()->count(3)->create();

        $this->mock(UserSuggestionService::class, function ($mock) use ($suggestedUsers) {
            $mock->shouldReceive('getSuggestions')
                ->with(\Mockery::any(), 10)
                ->andReturn($suggestedUsers);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/suggestions/users');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_user_can_limit_suggestions()
    {
        $user = User::factory()->create();
        $suggestedUsers = User::factory()->count(5)->create();

        $this->mock(UserSuggestionService::class, function ($mock) use ($suggestedUsers) {
            $mock->shouldReceive('getSuggestions')
                ->with(\Mockery::any(), 5)
                ->andReturn($suggestedUsers->take(5));
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/suggestions/users?limit=5');

        $response->assertStatus(200)
            ->assertJsonCount(5);
    }

    public function test_guest_cannot_get_suggestions()
    {
        $response = $this->getJson('/api/suggestions/users');

        $response->assertStatus(401);
    }
}