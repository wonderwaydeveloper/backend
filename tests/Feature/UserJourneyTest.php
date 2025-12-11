<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class UserJourneyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function complete_user_journey()
    {
        $this->withoutMiddleware([\App\Http\Middleware\TrackOnlineUser::class]);

        // کاربر اول
        $user = User::factory()->create([
            'name' => 'Integration User',
            'username' => 'journeyuser',
            'email' => 'journey@example.com',
            'password' => bcrypt('password123'),
            'birth_date' => '1990-01-01',
            'is_private' => false,
            'is_banned' => false,
        ]);

        Sanctum::actingAs($user);

        // آپدیت پروفایل
        $this->putJson('/api/users/me', [
            'bio' => 'Integration Bio',
            'location' => 'Test',
            'is_private' => false,
        ])->assertStatus(200);

        $user->refresh();

        // ساخت پست
        $postResponse = $this->postJson('/api/posts', [
            'content' => 'My first post!',
        ])->assertStatus(201);

        $post = $postResponse->json()['data'];
        $postModel = Post::find($post['id']);

        // کاربر اول نمی‌تواند پست خودش را لایک کند
        $this->postJson("/api/posts/{$post['id']}/like")->assertStatus(400);

        // کاربر دوم
        $user2 = User::factory()->create([
            'name' => 'Integration User2',
            'username' => 'journeyuser2',
            'email' => 'journey2@example.com',
            'password' => bcrypt('password123'),
            'birth_date' => '1990-01-01',
            'is_private' => false,
            'is_banned' => false,
        ]);

        Sanctum::actingAs($user2);

        // کاربر دوم پست را لایک می‌کند
        $this->postJson("/api/posts/{$post['id']}/like")->assertStatus(200);

        // کاربر دوم کاربر اول را فالو می‌کند
        $this->postJson("/api/users/{$user->id}/follow")->assertStatus(200);

        // کاربر دوم کامنت می‌گذارد
        $this->postJson('/api/comments', [
            'content' => 'Nice!',
            'commentable_type' => 'post',
            'commentable_id' => $post['id'],
        ])->assertStatus(201);

        // کاربر اول مقاله می‌سازد
        Sanctum::actingAs($user);
        $articleResponse = $this->postJson('/api/articles', [
            'title' => 'Test Article',
            'content' => 'Lorem ipsum',
            'excerpt' => 'short',
            'tags' => ['integration'],
            'status' => 'published',
        ])->assertStatus(201);

        $article = $articleResponse->json()['data'];

        // کاربر دوم مقاله را بوکمارک می‌کند
        Sanctum::actingAs($user2);
        $this->postJson("/api/articles/{$article['id']}/bookmark")->assertStatus(200);

        // Assertions نهایی
        $this->assertDatabaseHas('posts', ['id' => $post['id']]);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user2->id,
            'likeable_id' => $post['id'],
            'likeable_type' => Post::class
        ]);
        $this->assertDatabaseHas('comments', [
            'user_id' => $user2->id,
            'commentable_id' => $post['id'],
            'commentable_type' => Post::class
        ]);
        $this->assertDatabaseHas('articles', ['id' => $article['id']]);
        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user2->id,
            'bookmarkable_id' => $article['id'],
            'bookmarkable_type' => Article::class
        ]);
    }

    /** @test */
    public function simple_auth_issue()
    {
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);

        Sanctum::actingAs($user1);
        $response1 = $this->getJson('/api/auth/user');
        $this->assertEquals($user1->id, $response1->json()['data']['user']['id']);

        Sanctum::actingAs($user2);
        $response2 = $this->getJson('/api/auth/user');
        $this->assertEquals($user2->id, $response2->json()['data']['user']['id']);
    }
}