<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class FeedIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function feed_is_generated_correctly()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $followed = User::factory()->count(3)->create();
        $followedIds = $followed->pluck('id');

        $user->following()->sync(
            $followedIds->mapWithKeys(fn($id) => [$id => ['approved_at' => now()]])
        );

        foreach ($followed as $u)
            Post::factory()->count(2)->create(['user_id' => $u->id]);

        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/posts/feed/personal')
            ->assertStatus(200);

        $data = $response->json('data.data') ?? $response->json('data');
        $this->assertCount(8, $data);

        $this->assertEqualsCanonicalizing(
            [...$followedIds, $user->id],
            collect($data)->pluck('user.id')->unique()->toArray()
        );
    }
}
