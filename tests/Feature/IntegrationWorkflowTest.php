<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_post_like_workflow(): void
    {
        // Create users
        $author = User::factory()->create();
        $liker = User::factory()->create();

        // Author creates a post
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Test post content'
        ]);

        // User likes the post
        $post->likes()->create(['user_id' => $liker->id]);
        $post->increment('likes_count');

        // Verify like was created
        $this->assertTrue($post->likes()->where('user_id', $liker->id)->exists());
        $this->assertEquals(1, $post->fresh()->likes_count);

        // User unlikes the post
        $post->likes()->where('user_id', $liker->id)->delete();
        $post->decrement('likes_count');

        // Verify like was removed
        $this->assertFalse($post->likes()->where('user_id', $liker->id)->exists());
        $this->assertEquals(0, $post->fresh()->likes_count);
    }

    public function test_user_follow_workflow(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User1 follows User2
        $user1->following()->attach($user2->id);
        $user1->increment('following_count');
        $user2->increment('followers_count');

        // Verify follow relationship
        $this->assertTrue($user1->following()->where('following_id', $user2->id)->exists());
        $this->assertTrue($user2->followers()->where('follower_id', $user1->id)->exists());
        $this->assertEquals(1, $user1->fresh()->following_count);
        $this->assertEquals(1, $user2->fresh()->followers_count);

        // User1 unfollows User2
        $user1->following()->detach($user2->id);
        $user1->decrement('following_count');
        $user2->decrement('followers_count');

        // Verify unfollow
        $this->assertFalse($user1->following()->where('following_id', $user2->id)->exists());
        $this->assertEquals(0, $user1->fresh()->following_count);
        $this->assertEquals(0, $user2->fresh()->followers_count);
    }

    public function test_post_comment_workflow(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();

        // Create post
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'content' => 'Original post'
        ]);

        // Add comment
        $comment = $post->comments()->create([
            'user_id' => $commenter->id,
            'content' => 'Great post!'
        ]);
        $post->increment('comments_count');

        // Verify comment
        $this->assertTrue($post->comments()->where('user_id', $commenter->id)->exists());
        $this->assertEquals(1, $post->fresh()->comments_count);
        $this->assertEquals('Great post!', $comment->content);
    }

    public function test_hashtag_creation_workflow(): void
    {
        $user = User::factory()->create();

        // Create post with hashtags
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'content' => 'Learning #laravel and #php today!'
        ]);

        // Extract hashtags
        preg_match_all('/#([a-zA-Z0-9_]+)/', $post->content, $matches);
        $hashtags = $matches[1];

        $this->assertCount(2, $hashtags);
        $this->assertContains('laravel', $hashtags);
        $this->assertContains('php', $hashtags);
    }

    public function test_user_timeline_workflow(): void
    {
        $user = User::factory()->create();
        $followedUser1 = User::factory()->create();
        $followedUser2 = User::factory()->create();

        // User follows other users
        $user->following()->attach([$followedUser1->id, $followedUser2->id]);

        // Followed users create posts
        $post1 = Post::factory()->create([
            'user_id' => $followedUser1->id,
            'content' => 'Post from followed user 1'
        ]);
        
        $post2 = Post::factory()->create([
            'user_id' => $followedUser2->id,
            'content' => 'Post from followed user 2'
        ]);

        // Get timeline posts
        $timelinePosts = Post::whereIn('user_id', $user->following->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        $this->assertCount(2, $timelinePosts);
        $this->assertTrue($timelinePosts->contains('id', $post1->id));
        $this->assertTrue($timelinePosts->contains('id', $post2->id));
    }
}