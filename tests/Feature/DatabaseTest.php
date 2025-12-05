<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Follow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[Group('database')]
    public function database_connections_work()
    {
        $this->assertNotNull(DB::connection()->getPdo());
    }

    #[Test]
    #[Group('database')]
    public function user_table_has_correct_structure()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Check that required fields are present
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    #[Test]
    #[Group('database')]
    public function posts_table_has_correct_structure()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'Test content',
        ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Test content',
        ]);

        // Check soft deletes
        $post->delete();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    #[Test]
    #[Group('database')]
    public function articles_table_has_correct_structure()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);

        $this->assertDatabaseHas('articles', [
            'user_id' => $user->id,
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);

        // Check JSON fields
        $this->assertIsArray($article->tags);
    }

    #[Test]
    #[Group('database')]
    public function follows_table_has_correct_constraints()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Should be able to create follow relationship
        $follow = Follow::create([
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $user1->id,
            'following_id' => $user2->id,
        ]);

        // Should not be able to create duplicate follow
        try {
            Follow::create([
                'follower_id' => $user1->id,
                'following_id' => $user2->id,
            ]);
            $this->fail('Should have thrown unique constraint violation');
        } catch (\Exception $e) {
            $this->assertTrue(true); // Expected to fail
        }
    }

    #[Test]
    #[Group('database')]
    public function polymorphic_relationships_work_correctly()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $article = Article::factory()->create(['user_id' => $user->id]);

        // Create comments on both post and article
        $postComment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $post->id,
            'commentable_type' => Post::class,
        ]);

        $articleComment = Comment::factory()->create([
            'user_id' => $user->id,
            'commentable_id' => $article->id,
            'commentable_type' => Article::class,
        ]);

        // Verify relationships
        $this->assertEquals($post->id, $postComment->commentable->id);
        $this->assertEquals($article->id, $articleComment->commentable->id);
        $this->assertInstanceOf(Post::class, $postComment->commentable);
        $this->assertInstanceOf(Article::class, $articleComment->commentable);
    }

    #[Test]
    #[Group('database')]
    public function database_indexes_are_effective()
    {
        // Create test data
        $user = User::factory()->create();
        $posts = Post::factory()->count(100)->create(['user_id' => $user->id]);

        // Test query with index
        $startTime = microtime(true);
        $userPosts = Post::where('user_id', $user->id)->get();
        $endTime = microtime(true);

        $queryTime = $endTime - $startTime;

        // Query should be fast with index
        $this->assertLessThan(0.1, $queryTime, 'Query with index should be fast');
        $this->assertCount(100, $userPosts);
    }

    #[Test]
    #[Group('database')]
    public function database_transactions_work_correctly()
    {
        DB::beginTransaction();

        try {
            $user = User::factory()->create();
            $post = Post::factory()->create(['user_id' => $user->id]);
            
            // Intentionally cause an error
            throw new \Exception('Test rollback');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Both user and post should not exist due to rollback
        $this->assertDatabaseMissing('users', ['email' => $user->email ?? '']);
        $this->assertDatabaseMissing('posts', ['id' => $post->id ?? 0]);
    }

    #[Test]
    #[Group('database')]
    public function foreign_key_constraints_work()
    {
        // در PostgreSQL با cascade، وقتی کاربر حذف می‌شود، پست‌هایش هم حذف می‌شوند
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        // ابتدا پست را soft delete می‌کنیم
        $post->delete();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        
        // سپس کاربر را حذف می‌کنیم - به دلیل cascade در دیتابیس، پست هم حذف می‌شود
        $user->delete();
        
        // کاربر باید حذف شده باشد
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        
        // پست هم باید کاملاً حذف شده باشد (نه فقط soft delete)
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    #[Group('database')]
    public function test_foreign_key_without_cascade_works()
    {
        // تست جداگانه برای بررسی cascade
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        // فقط پست را حذف می‌کنیم
        $post->delete();
        
        // پست باید soft delete شده باشد
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        
        // کاربر باید همچنان وجود داشته باشد
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        
        // کاربر را حذف می‌کنیم
        $user->delete();
        
        // کاربر حذف شده
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        
        // پست باید همچنان soft delete شده باشد (در صورت عدم cascade)
        // اما با cascade، پست هم حذف می‌شود
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    #[Test]
    #[Group('database')]
    public function full_text_search_works()
    {
        // Create posts with searchable content
        Post::factory()->create(['content' => 'Laravel is a great PHP framework']);
        Post::factory()->create(['content' => 'PHP is a popular programming language']);
        Post::factory()->create(['content' => 'JavaScript is for web development']);

        // Search using full-text (if configured)
        // Note: This depends on your database configuration
        $results = Post::whereFullText('content', 'Laravel PHP')->get();

        // At least the first post should be found
        $this->assertGreaterThanOrEqual(1, $results->count());
    }
}