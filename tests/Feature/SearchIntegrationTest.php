<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class SearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function search_returns_expected_results()
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Post::factory()->create(['content' => 'Laravel PHP post']);
        Post::factory()->create(['content' => 'React JavaScript post']);

        Sanctum::actingAs(User::factory()->create());

        $res = $this->getJson('/api/search?q=laravel')
            ->assertStatus(200)
            ->json('data');

        $this->assertNotEmpty($res['posts']);
    }
}
