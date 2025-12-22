<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraphQLTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_query_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(3)->create(['user_id' => $user->id]);

        $query = '{ posts { id, content, user { name, username } } }';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/graphql', ['query' => $query]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'posts' => [
                        '*' => ['id', 'content', 'user']
                    ]
                ]
            ]);
    }

    public function test_can_query_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $query = "{ user(id: {$user->id}) { name, username } }";

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/graphql', ['query' => $query]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['name', 'username']
                ]
            ]);
    }

    public function test_can_query_timeline(): void
    {
        $user = User::factory()->create();
        $followedUser = User::factory()->create();
        
        $user->following()->attach($followedUser->id);
        
        Post::factory()->create(['user_id' => $followedUser->id]);
        Post::factory()->create(['user_id' => $user->id]);

        $query = '{ timeline { id, content, likes_count } }';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/graphql', ['query' => $query]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'timeline' => [
                        '*' => ['id', 'content', 'likes_count']
                    ]
                ]
            ]);
    }

    public function test_graphql_requires_authentication(): void
    {
        $query = '{ timeline { id, content } }';

        $response = $this->postJson('/api/graphql', ['query' => $query]);

        $response->assertStatus(401);
    }

    public function test_graphql_handles_invalid_query(): void
    {
        $user = User::factory()->create();
        $query = '{ invalidQuery { field } }';

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/graphql', ['query' => $query]);

        $response->assertStatus(400)
            ->assertJsonStructure(['errors']);
    }

    public function test_graphql_with_variables(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(5)->create();

        $query = '{ posts { id, content } }';
        $variables = ['limit' => 3];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/graphql', [
                'query' => $query,
                'variables' => $variables
            ]);

        $response->assertStatus(200);
    }
}