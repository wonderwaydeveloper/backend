<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GiphyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GifTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_gifs()
    {
        $user = User::factory()->create();

        $this->mock(GiphyService::class, function ($mock) {
            $mock->shouldReceive('search')
                ->with('funny', 20)
                ->andReturn([
                    'data' => [
                        ['id' => '1', 'url' => 'https://giphy.com/1'],
                        ['id' => '2', 'url' => 'https://giphy.com/2'],
                    ]
                ]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/gifs/search?q=funny');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'url']
                ]
            ]);
    }

    public function test_user_can_get_trending_gifs()
    {
        $user = User::factory()->create();

        $this->mock(GiphyService::class, function ($mock) {
            $mock->shouldReceive('trending')
                ->with(20)
                ->andReturn([
                    'data' => [
                        ['id' => '1', 'url' => 'https://giphy.com/trending1'],
                        ['id' => '2', 'url' => 'https://giphy.com/trending2'],
                    ]
                ]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/gifs/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'url']
                ]
            ]);
    }

    public function test_gif_search_requires_query()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/gifs/search');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_gif_search_validates_limit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/gifs/search?q=test&limit=100');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['limit']);
    }

    public function test_guest_cannot_search_gifs()
    {
        $response = $this->getJson('/api/gifs/search?q=test');

        $response->assertStatus(401);
    }
}