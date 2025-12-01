<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = app(PostService::class);
    }

    /** @test */
    public function it_can_create_post()
    {
        $user = User::factory()->create();
        
        $data = [
            'content' => 'Test post content',
            'type' => 'post',
        ];

        $post = $this->postService->createPost($user, $data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('Test post content', $post->content);
        $this->assertEquals($user->id, $post->user_id);
        $this->assertDatabaseHas('posts', [
            'content' => 'Test post content',
            'user_id' => $user->id,
        ]);
    }

    /** @test */
    public function it_increments_user_posts_count()
    {
        $user = User::factory()->create(['posts_count' => 0]);
        
        $this->postService->createPost($user, ['content' => 'Post 1']);
        $this->postService->createPost($user, ['content' => 'Post 2']);

        $this->assertEquals(2, $user->fresh()->posts_count);
    }

    /** @test */
    public function it_can_toggle_like_on_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['like_count' => 0]);

        // Like the post
        $liked = $this->postService->toggleLike($user, $post);
        $this->assertTrue($liked);
        $this->assertEquals(1, $post->fresh()->like_count);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
        ]);

        // Unlike the post
        $liked = $this->postService->toggleLike($user, $post);
        $this->assertFalse($liked);
        $this->assertEquals(0, $post->fresh()->like_count);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_id' => $post->id,
        ]);
    }

    /** @test */
    public function it_can_repost_a_post()
    {
        $user = User::factory()->create();
        $originalPost = Post::factory()->create(['repost_count' => 0]);

        $repost = $this->postService->repost($user, $originalPost);

        $this->assertInstanceOf(Post::class, $repost);
        $this->assertEquals('quote', $repost->type);
        $this->assertEquals($originalPost->id, $repost->original_post_id);
        $this->assertEquals(1, $originalPost->fresh()->repost_count);
    }

    /** @test */
    public function it_filters_sensitive_content_for_underage_users()
    {
        $underageUser = User::factory()->create(['is_underage' => true]);
        $adultUser = User::factory()->create(['is_underage' => false]);

        Post::factory()->create(['is_sensitive' => true]);
        Post::factory()->create(['is_sensitive' => false]);
        Post::factory()->create(['is_sensitive' => true]);
        Post::factory()->create(['is_sensitive' => false]);

        // For underage user
        $underagePosts = $this->postService->getPosts($underageUser, []);
        $this->assertEquals(2, $underagePosts->total()); // Only non-sensitive posts

        // For adult user
        $adultPosts = $this->postService->getPosts($adultUser, []);
        $this->assertEquals(4, $adultPosts->total()); // All posts
    }
}