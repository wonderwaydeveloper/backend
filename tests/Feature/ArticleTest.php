<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_create_article()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/articles', [
            'title' => 'Test Article Title',
            'content' => 'This is the article content',
            'excerpt' => 'Short excerpt',
            'tags' => ['php', 'laravel', 'testing'],
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Test Article Title')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.tags', ['php', 'laravel', 'testing']);

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article Title',
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function user_can_publish_their_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => 'draft',
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/articles/{$article->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'published');

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    /** @test */
    public function admin_can_approve_article()
    {
        $admin = User::factory()->create(['username' => 'admin']);
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
            'is_approved' => false,
        ]);
        
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('data.is_approved', true)
            ->assertJsonPath('data.approver.id', $admin->id);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'is_approved' => true,
            'approved_by' => $admin->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_approve_article()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'is_approved' => false,
        ]);
        
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/articles/{$article->id}/approve");

        $response->assertStatus(403);
    }

    /** @test */
    public function draft_articles_are_only_visible_to_author()
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $author->id,
            'status' => 'draft',
        ]);
        
        $token = $viewer->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/articles/{$article->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function article_view_count_increments_on_view()
    {
        $article = Article::factory()->create([
            'status' => 'published',
            'view_count' => 0,
        ]);
        
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200);
        $this->assertEquals(1, $article->fresh()->view_count);
    }
}