<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class IntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_user_journey_test()
    {
        // 1. User Registration
        $registerResponse = $this->postJson('/api/auth/register', [
            'name' => 'Integration Test User',
            'username' => 'integrationuser',
            'email' => 'integration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'birth_date' => '1990-01-01',
        ]);

        $registerResponse->assertStatus(200);
        $userData = $registerResponse->json('data.user');
        $token = $registerResponse->json('data.access_token');

        // 2. Login with the new account
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        // 3. Update Profile
        $updateResponse = $this->putJson('/api/users/me', [
            'bio' => 'This is my bio from integration test',
            'location' => 'Test City',
        ]);

        $updateResponse->assertStatus(200);

        // 4. Create a Post
        $postResponse = $this->postJson('/api/posts', [
            'content' => 'My first post from integration test!',
        ]);

        $postResponse->assertStatus(201);
        $postId = $postResponse->json('data.id');

        // 5. Like the Post (can't like own post - should fail)
        $likeResponse = $this->postJson("/api/posts/{$postId}/like");
        $likeResponse->assertStatus(400); // Can't like own post

        // 6. Create Another User and Interact
        $user2 = User::factory()->create();
        $user2Token = $user2->createToken('test-token')->plainTextToken;

        // User2 likes the post
        $this->withHeaders(['Authorization' => 'Bearer ' . $user2Token]);
        $likeResponse2 = $this->postJson("/api/posts/{$postId}/like");
        $likeResponse2->assertStatus(200)
            ->assertJson(['data' => ['liked' => true]]);

        // 7. User2 follows the first user
        $followResponse = $this->postJson("/api/users/{$userData['id']}/follow");
        $followResponse->assertStatus(200);

        // 8. User2 comments on the post
        $commentResponse = $this->postJson('/api/comments', [
            'content' => 'Great post from integration test!',
            'commentable_type' => 'post',
            'commentable_id' => $postId,
        ]);

        $commentResponse->assertStatus(201);

        // 9. Switch back to first user
        $this->withHeaders(['Authorization' => 'Bearer ' . $token]);

        // 10. First user accepts follow request (if private account)
        $user = User::find($userData['id']);
        if ($user->is_private) {
            $acceptResponse = $this->postJson("/api/users/{$user2->id}/accept-follow-request");
            $acceptResponse->assertStatus(200);
        }

        // 11. Create an Article
        $articleResponse = $this->postJson('/api/articles', [
            'title' => 'Integration Test Article',
            'content' => 'This is article content from integration test.',
            'excerpt' => 'Short excerpt',
            'tags' => ['test', 'integration'],
            'status' => 'published',
        ]);

        $articleResponse->assertStatus(201);
        $articleId = $articleResponse->json('data.id');

        // 12. User2 bookmarks the article
        $this->withHeaders(['Authorization' => 'Bearer ' . $user2Token]);
        $bookmarkResponse = $this->postJson("/api/articles/{$articleId}/bookmark");
        $bookmarkResponse->assertStatus(200);

        // 13. Verify all interactions are recorded
        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'content' => 'My first post from integration test!',
        ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user2->id,
            'likeable_id' => $postId,
            'likeable_type' => Post::class,
        ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $user2->id,
            'commentable_id' => $postId,
            'commentable_type' => Post::class,
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $articleId,
            'title' => 'Integration Test Article',
        ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user2->id,
            'bookmarkable_id' => $articleId,
            'bookmarkable_type' => Article::class,
        ]);
    }

    /** @test */
    public function feed_generation_integration_test()
    {
        // Create main user
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create users to follow
        $usersToFollow = User::factory()->count(3)->create();

        // Follow users
        foreach ($usersToFollow as $userToFollow) {
            Follow::create([
                'follower_id' => $user->id,
                'following_id' => $userToFollow->id,
                'approved_at' => now(),
            ]);
        }

        // Create posts by followed users
        foreach ($usersToFollow as $userToFollow) {
            Post::factory()->count(2)->create(['user_id' => $userToFollow->id]);
        }

        // Create own posts
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        // Get personal feed
        $response = $this->getJson('/api/posts/feed/personal');

        $response->assertStatus(200);
        
        // Should see posts from followed users + own posts
        // 3 users * 2 posts + 2 own posts = 8 posts
        $response->assertJsonCount(8, 'data');

        // Verify feed contains posts from correct users
        $feedPosts = $response->json('data');
        $userIdsInFeed = collect($feedPosts)->pluck('user.id')->unique()->toArray();

        // Should include user's own ID and all followed users' IDs
        $expectedUserIds = array_merge(
            [$user->id],
            $usersToFollow->pluck('id')->toArray()
        );

        sort($userIdsInFeed);
        sort($expectedUserIds);

        $this->assertEquals($expectedUserIds, $userIdsInFeed);
    }

    /** @test */
    public function notification_system_integration_test()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user2);

        // User2 follows user1
        $followResponse = $this->postJson("/api/users/{$user1->id}/follow");
        $followResponse->assertStatus(200);

        // Switch to user1
        Sanctum::actingAs($user1);

        // If user1 is private, accept follow request
        if ($user1->is_private) {
            $acceptResponse = $this->postJson("/api/users/{$user2->id}/accept-follow-request");
            $acceptResponse->assertStatus(200);
        }

        // User2 creates a post
        Sanctum::actingAs($user2);
        $postResponse = $this->postJson('/api/posts', [
            'content' => 'Post for notification test',
        ]);
        $postResponse->assertStatus(201);
        $postId = $postResponse->json('data.id');

        // User1 likes the post
        Sanctum::actingAs($user1);
        $likeResponse = $this->postJson("/api/posts/{$postId}/like");
        $likeResponse->assertStatus(200);

        // User2 should get a notification for the like
        Sanctum::actingAs($user2);
        $notificationsResponse = $this->getJson('/api/notifications');

        $notificationsResponse->assertStatus(200);
        
        // Should have at least one notification (for like)
        $notifications = $notificationsResponse->json('data.data');
        $this->assertNotEmpty($notifications);

        // Check if there's a like notification
        $likeNotification = collect($notifications)->first(function ($notification) use ($user1) {
            return $notification['data']['type'] === 'new_like' && 
                   $notification['data']['liker_id'] === $user1->id;
        });

        $this->assertNotNull($likeNotification, 'Like notification should exist');
    }

    /** @test */
    public function search_functionality_integration_test()
    {
        // Create test data
        $john = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $jane = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);
        
        Post::factory()->create([
            'user_id' => $john->id,
            'content' => 'Post about Laravel and PHP development',
        ]);
        
        Post::factory()->create([
            'user_id' => $jane->id,
            'content' => 'Post about React and JavaScript',
        ]);
        
        Article::factory()->create([
            'user_id' => $john->id,
            'title' => 'Laravel Best Practices',
            'content' => 'Article about Laravel framework',
            'status' => 'published',
        ]);
        
        Article::factory()->create([
            'user_id' => $jane->id,
            'title' => 'React Hooks Guide',
            'content' => 'Article about React hooks',
            'status' => 'published',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test global search
        $response = $this->getJson('/api/search?q=laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'posts',
                    'articles',
                ],
            ]);

        $searchData = $response->json('data');
        
        // Should find John (user), John's post, and John's article
        $this->assertNotEmpty($searchData['users']);
        $this->assertNotEmpty($searchData['posts']);
        $this->assertNotEmpty($searchData['articles']);

        // Test user search
        $userSearchResponse = $this->getJson('/api/users/search?query=john');
        $userSearchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data'); // Should find John Doe
    }
}