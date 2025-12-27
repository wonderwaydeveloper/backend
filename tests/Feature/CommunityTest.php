<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_community(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/communities', [
                'name' => 'Test Community',
                'description' => 'A test community',
                'privacy' => 'public',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'community' => [
                    'id',
                    'name',
                    'description',
                    'slug',
                    'privacy',
                    'member_count',
                    'creator',
                ]
            ]);

        $this->assertDatabaseHas('communities', [
            'name' => 'Test Community',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('community_members', [
            'community_id' => Community::first()->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_user_can_join_public_community(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $community = Community::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/communities/{$community->id}/join");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Joined successfully']);

        $this->assertDatabaseHas('community_members', [
            'community_id' => $community->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_user_can_request_to_join_private_community(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $community = Community::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'private',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/communities/{$community->id}/join");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Join request sent']);

        $this->assertDatabaseHas('community_join_requests', [
            'community_id' => $community->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_leave_community(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        
        $community = Community::factory()->create([
            'created_by' => $owner->id,
        ]);

        $community->members()->attach($user->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/communities/{$community->id}/leave");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Left community successfully']);

        $this->assertDatabaseMissing('community_members', [
            'community_id' => $community->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_owner_cannot_leave_community(): void
    {
        $owner = User::factory()->create();
        
        $community = Community::factory()->create([
            'created_by' => $owner->id,
        ]);

        $community->members()->attach($owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($owner, 'sanctum')
            ->postJson("/api/communities/{$community->id}/leave");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Owner cannot leave community']);
    }

    public function test_moderator_can_approve_join_request(): void
    {
        $owner = User::factory()->create();
        $moderator = User::factory()->create();
        $user = User::factory()->create();
        
        $community = Community::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'private',
        ]);

        $community->members()->attach($moderator->id, [
            'role' => 'moderator',
            'joined_at' => now(),
        ]);

        $joinRequest = $community->joinRequests()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($moderator, 'sanctum')
            ->postJson("/api/communities/{$community->id}/join-requests/{$joinRequest->id}/approve");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Request approved']);

        $this->assertDatabaseHas('community_join_requests', [
            'id' => $joinRequest->id,
            'status' => 'approved',
            'reviewed_by' => $moderator->id,
        ]);

        $this->assertDatabaseHas('community_members', [
            'community_id' => $community->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);
    }

    public function test_guest_cannot_create_community(): void
    {
        $response = $this->postJson('/api/communities', [
            'name' => 'Test Community',
            'description' => 'A test community',
            'privacy' => 'public',
        ]);

        $response->assertStatus(401);
    }

    public function test_community_name_must_be_unique(): void
    {
        $user = User::factory()->create();
        Community::factory()->create(['name' => 'Existing Community']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/communities', [
                'name' => 'Existing Community',
                'description' => 'A test community',
                'privacy' => 'public',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_view_community_members(): void
    {
        $user = User::factory()->create();
        $community = Community::factory()->create();
        
        // Add some members
        $members = User::factory(3)->create();
        foreach ($members as $member) {
            $community->members()->attach($member->id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/communities/{$community->id}/members");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}