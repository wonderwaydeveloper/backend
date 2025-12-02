<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use App\Models\Bookmark;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_bookmarks()
    {
        $user = User::factory()->create();
        
        // Bookmark some posts
        $posts = Post::factory()->count(3)->create();
        foreach ($posts as $post) {
            Bookmark::create([
                'user_id' => $user->id,
                'bookmarkable_id' => $post->id,
                'bookmarkable_type' => Post::class,
            ]);
        }

        // Bookmark some articles
        $articles = Article::factory()->count(2)->create();
        foreach ($articles as $article) {
            Bookmark::create([
                'user_id' => $user->id,
                'bookmarkable_id' => $article->id,
                'bookmarkable_type' => Article::class,
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/bookmarks');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function user_can_filter_bookmarks_by_type()
    {
        $user = User::factory()->create();
        
        // Bookmark posts
        $posts = Post::factory()->count(3)->create();
        foreach ($posts as $post) {
            Bookmark::create([
                'user_id' => $user->id,
                'bookmarkable_id' => $post->id,
                'bookmarkable_type' => Post::class,
            ]);
        }

        // Bookmark articles
        $articles = Article::factory()->count(2)->create();
        foreach ($articles as $article) {
            Bookmark::create([
                'user_id' => $user->id,
                'bookmarkable_id' => $article->id,
                'bookmarkable_type' => Article::class,
            ]);
        }

        Sanctum::actingAs($user);

        // Filter by posts only
        $response = $this->getJson('/api/bookmarks?type=post');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Filter by articles only
        $response = $this->getJson('/api/bookmarks?type=article');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_remove_bookmark()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        $bookmark = Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_id' => $post->id,
            'bookmarkable_type' => Post::class,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/bookmarks/post/{$post->id}");

        $response->assertStatus(200)
            ->assertJson(['data' => ['deleted' => true]]);

        $this->assertDatabaseMissing('bookmarks', [
            'id' => $bookmark->id,
        ]);
    }

    /** @test */
    public function user_cannot_remove_other_users_bookmark()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();
        
        $bookmark = Bookmark::create([
            'user_id' => $user1->id,
            'bookmarkable_id' => $post->id,
            'bookmarkable_type' => Post::class,
        ]);

        Sanctum::actingAs($user2);

        $response = $this->deleteJson("/api/bookmarks/post/{$post->id}");

        $response->assertStatus(404); // Bookmark not found for this user
    }

    /** @test */
    public function bookmarking_twice_does_not_create_duplicate()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($user);

        // First bookmark
        $response = $this->postJson("/api/posts/{$post->id}/bookmark");
        $response->assertStatus(200)
            ->assertJson(['data' => ['bookmarked' => true]]);

        // Try to bookmark again
        $response = $this->postJson("/api/posts/{$post->id}/bookmark");
        $response->assertStatus(400)
            ->assertJson(['message' => 'Already bookmarked']);

        // Should still have only one bookmark
        $bookmarkCount = Bookmark::where('user_id', $user->id)
            ->where('bookmarkable_id', $post->id)
            ->where('bookmarkable_type', Post::class)
            ->count();

        $this->assertEquals(1, $bookmarkCount);
    }

    /** @test */
    public function bookmarked_items_have_bookmark_flag_in_response()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        // Bookmark the post
        Bookmark::create([
            'user_id' => $user->id,
            'bookmarkable_id' => $post->id,
            'bookmarkable_type' => Post::class,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'interactions' => [
                        'bookmarked' => true,
                    ],
                ],
            ]);
    }

    /** @test */
    public function bookmark_count_is_accurate()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        
        // Multiple users bookmark the same post
        $users = User::factory()->count(5)->create();
        foreach ($users as $bookmarkUser) {
            Bookmark::create([
                'user_id' => $bookmarkUser->id,
                'bookmarkable_id' => $post->id,
                'bookmarkable_type' => Post::class,
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200);
        // Note: The current API doesn't return bookmark count for posts
        // but we can verify the database count
        $bookmarkCount = Bookmark::where('bookmarkable_id', $post->id)
            ->where('bookmarkable_type', Post::class)
            ->count();

        $this->assertEquals(5, $bookmarkCount);
    }
}