<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class NotificationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function like_notification_appears()
    {
        $user1 = User::factory()->create(['is_private' => false]);
        $user2 = User::factory()->create();

        Sanctum::actingAs($user2);

        $this->postJson("/api/users/{$user1->id}/follow")
            ->assertStatus(200);

        $postId = $this->postJson('/api/posts', [
            'content' => 'Notif test'
        ])->assertStatus(201)['data']['id'];

        Sanctum::actingAs($user1);

        $this->postJson("/api/posts/{$postId}/like")
            ->assertStatus(200);

        $response = $this->getJson('/api/notifications')
            ->assertStatus(200);

        $notifs = $response->json('data.data') ?? $response->json('data');

        $this->assertNotEmpty($notifs);

        $this->assertTrue(
            collect($notifs)->contains(fn($n) =>
                isset($n['data']['type']) &&
                $n['data']['type'] === 'new_like'
            )
        );
    }
}
