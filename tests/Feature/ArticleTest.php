<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_article()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/articles', [
            'title' => 'Test Article Title',
            'content' => 'This is the article content.',
            'excerpt' => 'Short excerpt',
            'tags' => ['laravel', 'testing', 'php'],
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'slug',
                    'content',
                    'status',
                    'user' => ['id', 'name'],
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article Title',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function author_can_publish_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/articles/{$article->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'status' => 'published',
                ],
            ]);

        $this->assertNotNull($article->fresh()->published_at);
    }

    /** @test */
    public function admin_can_approve_article()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $article = Article::factory()->create([
            'status' => 'published',
            'is_approved' => false,
        ]);
        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'is_approved' => true,
                ],
            ]);

        $this->assertNotNull($article->fresh()->approved_at);
        $this->assertEquals($admin->id, $article->fresh()->approved_by);
    }

    /** @test */
    public function non_admin_cannot_approve_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_like_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/articles/{$article->id}/like");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'liked' => true,
                    'like_count' => 1,
                ],
            ]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $article->id,
            'likeable_type' => Article::class,
        ]);
    }

    /** @test */
    public function user_can_bookmark_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/articles/{$article->id}/bookmark");

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['bookmarked' => true],
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $user->id,
            'bookmarkable_id' => $article->id,
            'bookmarkable_type' => Article::class,
        ]);
    }

    /** @test */
    public function draft_article_only_visible_to_author()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => 'draft',
        ]);

        // Author can view draft
        Sanctum::actingAs($author);
        $response = $this->getJson("/api/articles/{$article->id}");
        $response->assertStatus(200);

        // Other user cannot view draft
        Sanctum::actingAs($otherUser);
        $response = $this->getJson("/api/articles/{$article->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function published_article_visible_to_everyone()
    {
        $article = Article::factory()->create([
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $article->id,
                    'status' => 'published',
                ],
            ]);
    }

    /** @test */
    public function article_view_count_increments()
    {
        $article = Article::factory()->create(['status' => 'published']);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $initialViews = $article->view_count;

        $response = $this->getJson("/api/articles/{$article->id}");
        $response->assertStatus(200);

        $this->assertEquals($initialViews + 1, $article->fresh()->view_count);
    }

    /** @test */
    public function user_can_view_articles_by_tag()
    {
        Article::factory()->create([
            'tags' => ['laravel', 'php'],
            'status' => 'published',
        ]);
        
        Article::factory()->create([
            'tags' => ['php', 'javascript'],
            'status' => 'published',
        ]);
        
        Article::factory()->create([
            'tags' => ['python'],
            'status' => 'published',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/articles?tag=php');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_own_articles()
    {
        $user = User::factory()->create();
        Article::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'published',
        ]);
        
        Article::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/articles/user/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data'); // Should see both published and draft
    }

    /** @test */
    public function other_user_can_only_view_published_articles()
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        
        Article::factory()->count(2)->create([
            'user_id' => $author->id,
            'status' => 'published',
        ]);
        
        Article::factory()->count(2)->create([
            'user_id' => $author->id,
            'status' => 'draft',
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson("/api/articles/user/{$author->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data'); // Only published articles
    }

    /** @test */
    public function scheduled_article_not_visible_until_published()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => 'scheduled',
            'scheduled_at' => now()->addDays(7),
            'published_at' => null,
        ]);

        // Author can view scheduled article
        Sanctum::actingAs($author);
        $response = $this->getJson("/api/articles/{$article->id}");
        $response->assertStatus(200);

        // Other user cannot view scheduled article
        Sanctum::actingAs($otherUser);
        $response = $this->getJson("/api/articles/{$article->id}");
        $response->assertStatus(403);
    }
}