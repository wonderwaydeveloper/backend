<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_own_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'username',
                    'email',
                    'bio',
                    'avatar',
                    'followers_count',
                    'following_count',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_profile()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->putJson('/api/users/me', [
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
            'location' => 'Tehran, Iran',
            'website' => 'https://example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'bio' => 'Updated bio information',
                    'location' => 'Tehran, Iran',
                    'website' => 'https://example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'bio' => 'Updated bio information',
        ]);
    }

    /** @test */
    public function user_can_upload_avatar()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $avatar = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->postJson('/api/users/me/avatar', [
            'avatar' => $avatar,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['avatar'],
            ]);

        $this->assertNotNull($user->fresh()->avatar);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    /** @test */
    public function user_can_follow_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/users/{$user2->id}/follow");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'following' => true,
                    'requires_approval' => false,
                ],
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    /** @test */
    public function user_cannot_follow_themselves()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/users/{$user->id}/follow");

        $response->assertStatus(400)
            ->assertJson(['message' => 'Cannot follow yourself']);
    }

    /** @test */
    public function user_can_unfollow_another_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Follow::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
            'approved_at' => now(),
        ]);

        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/users/{$user2->id}/unfollow");

        $response->assertStatus(200)
            ->assertJson(['data' => ['unfollowed' => true]]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);
    }

    /** @test */
    public function user_can_view_followers()
    {
        $user = User::factory()->create();
        $followers = User::factory()->count(3)->create();

        foreach ($followers as $follower) {
            Follow::create([
                'follower_id' => $follower->id,
                'following_id' => $user->id,
                'approved_at' => now(),
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/followers");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_following()
    {
        $user = User::factory()->create();
        $following = User::factory()->count(3)->create();

        foreach ($following as $followed) {
            Follow::create([
                'follower_id' => $user->id,
                'following_id' => $followed->id,
                'approved_at' => now(),
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/users/{$user->id}/following");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function private_user_requires_approval_for_follow_requests()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();
        Sanctum::actingAs($follower);

        $response = $this->postJson("/api/users/{$privateUser->id}/follow");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'following' => true,
                    'requires_approval' => true,
                ],
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => null,
        ]);
    }

    /** @test */
    public function private_user_can_see_follow_requests()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $followers = User::factory()->count(2)->create();

        foreach ($followers as $follower) {
            Follow::create([
                'follower_id' => $follower->id,
                'following_id' => $privateUser->id,
                'approved_at' => null,
            ]);
        }

        Sanctum::actingAs($privateUser);

        $response = $this->getJson('/api/users/me/follow-requests');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function private_user_can_accept_follow_request()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => null,
        ]);

        Sanctum::actingAs($privateUser);

        $response = $this->postJson("/api/users/{$follower->id}/accept-follow-request");

        $response->assertStatus(200)
            ->assertJson(['data' => ['accepted' => true]]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => now(),
        ]);
    }

    /** @test */
    public function private_user_can_reject_follow_request()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => null,
        ]);

        Sanctum::actingAs($privateUser);

        $response = $this->postJson("/api/users/{$follower->id}/reject-follow-request");

        $response->assertStatus(200)
            ->assertJson(['data' => ['rejected' => true]]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
        ]);
    }

    /** @test */
    public function user_can_search_for_other_users()
    {
        User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);
        User::factory()->create(['name' => 'Bob Johnson', 'username' => 'bob']);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/search?query=john');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // John Doe and Bob Johnson
    }

    /** @test */
    public function user_cannot_view_private_profile_without_following()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/users/{$privateUser->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_public_profile()
    {
        $publicUser = User::factory()->create(['is_private' => false]);
        $otherUser = User::factory()->create();
        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/users/{$publicUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $publicUser->id,
                    'username' => $publicUser->username,
                ],
            ]);
    }
}