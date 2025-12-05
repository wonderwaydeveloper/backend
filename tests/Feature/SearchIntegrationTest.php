<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SearchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function search_returns_expected_results()
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        Post::factory()->create(['content' => 'Laravel PHP post']);
        Post::factory()->create(['content' => 'React JavaScript post']);

        Article::factory()->create([
            'title' => 'Laravel Guide',
            'status' => 'published'
        ]);

        Article::factory()->create([
            'title' => 'React Hooks',
            'status' => 'published'
        ]);

        Sanctum::actingAs(User::factory()->create());

        $res = $this->getJson('/api/search?q=laravel')
            ->assertStatus(200)
            ->json('data');

        $this->assertNotEmpty($res['posts']);
        $this->assertNotEmpty($res['articles']);
    }
}
