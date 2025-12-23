<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimplePostServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_creation_logic(): void
    {
        $user = User::factory()->create();
        $postData = [
            'content' => 'This is a test post #laravel @user',
            'user_id' => $user->id
        ];

        $post = Post::create($postData);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('This is a test post #laravel @user', $post->content);
        $this->assertEquals($user->id, $post->user_id);
    }

    public function test_hashtag_extraction(): void
    {
        $content = 'This is a post with #laravel and #php hashtags';
        
        preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $matches);
        $hashtags = $matches[1];

        $this->assertCount(2, $hashtags);
        $this->assertContains('laravel', $hashtags);
        $this->assertContains('php', $hashtags);
    }

    public function test_mention_extraction(): void
    {
        $content = 'Hello @john and @jane how are you?';
        
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $mentions = $matches[1];

        $this->assertCount(2, $mentions);
        $this->assertContains('john', $mentions);
        $this->assertContains('jane', $mentions);
    }

    public function test_post_like_functionality(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Like the post
        $post->likes()->create(['user_id' => $user->id]);
        $post->increment('likes_count');

        $this->assertEquals(1, $post->fresh()->likes_count);
        $this->assertTrue($post->likes()->where('user_id', $user->id)->exists());
    }

    public function test_post_unlike_functionality(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['likes_count' => 1]);
        $post->likes()->create(['user_id' => $user->id]);

        // Unlike the post
        $post->likes()->where('user_id', $user->id)->delete();
        $post->decrement('likes_count');

        $this->assertEquals(0, $post->fresh()->likes_count);
        $this->assertFalse($post->likes()->where('user_id', $user->id)->exists());
    }

    public function test_draft_post_functionality(): void
    {
        $user = User::factory()->create();
        
        $draftPost = Post::create([
            'user_id' => $user->id,
            'content' => 'Draft post',
            'is_draft' => true,
            'published_at' => null
        ]);

        $this->assertTrue($draftPost->is_draft);
        $this->assertNull($draftPost->published_at);

        // Publish the draft
        $draftPost->update([
            'is_draft' => false,
            'published_at' => now()
        ]);

        $this->assertFalse($draftPost->fresh()->is_draft);
        $this->assertNotNull($draftPost->fresh()->published_at);
    }
}