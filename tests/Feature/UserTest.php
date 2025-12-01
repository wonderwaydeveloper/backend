<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_view_their_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email);
    }

    /** @test */
    public function user_can_update_their_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/users/me', [
            'name' => 'Updated Name',
            'bio' => 'This is my new bio',
            'website' => 'https://example.com',
            'location' => 'Tehran, Iran',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Updated Name')
            ->assertJsonPath('data.user.bio', 'This is my new bio');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'bio' => 'This is my new bio',
        ]);
    }

    /** @test */
    public function user_can_follow_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/users/{$user2->id}/follow");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully followed user']);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    /** @test */
    public function user_cannot_follow_themselves()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/users/{$user->id}/follow");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot follow yourself']);
    }

    /** @test */
    public function user_can_unfollow_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user1->following()->attach($user2->id);
        
        $token = $user1->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/users/{$user2->id}/unfollow");

        $response->assertStatus(200)
            ->assertJson(['message' => 'User unfollowed successfully']);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    /** @test */
    public function user_can_view_their_followers()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $user2->followers()->attach($user1->id);
        
        $token = $user2->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/users/{$user2->id}/followers");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user1->id);
    }

    /** @test */
    public function private_user_profile_requires_follow_approval()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();
        
        $token = $follower->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/users/{$privateUser->id}/follow");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Follow request sent']);

        // Check that follow request is pending approval
        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => null,
        ]);
    }

    /** @test */
    public function user_can_search_for_other_users()
    {
        User::factory()->create(['username' => 'john_doe', 'name' => 'John Doe']);
        User::factory()->create(['username' => 'jane_doe', 'name' => 'Jane Doe']);
        
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users/search?query=doe');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}