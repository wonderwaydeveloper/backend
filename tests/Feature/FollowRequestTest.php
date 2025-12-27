<?php

namespace Tests\Feature;

use App\Models\FollowRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_send_follow_request()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create(['is_private' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/users/{$targetUser->id}/follow-request");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Follow request sent']);

        $this->assertDatabaseHas('follow_requests', [
            'follower_id' => $user->id,
            'following_id' => $targetUser->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_view_follow_requests()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/follow-requests');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'follower_id', 'following_id', 'status']
                ]
            ]);
    }

    public function test_user_can_accept_follow_request()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        $followRequest = FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/follow-requests/{$followRequest->id}/accept");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Follow request accepted']);

        $this->assertDatabaseHas('follow_requests', [
            'id' => $followRequest->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_reject_follow_request()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        $followRequest = FollowRequest::create([
            'follower_id' => $requester->id,
            'following_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/follow-requests/{$followRequest->id}/reject");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Follow request rejected']);

        $this->assertDatabaseHas('follow_requests', [
            'id' => $followRequest->id,
            'status' => 'rejected',
        ]);
    }

    public function test_cannot_send_follow_request_to_self()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/users/{$user->id}/follow-request");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot send follow request to yourself']);
    }

    public function test_guest_cannot_send_follow_request()
    {
        $user = User::factory()->create();

        $response = $this->postJson("/api/users/{$user->id}/follow-request");

        $response->assertStatus(401);
    }
}