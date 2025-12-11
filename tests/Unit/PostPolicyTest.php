<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PostPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PostPolicy();
    }

    /** @test */
    public function user_can_view_public_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertTrue($this->policy->view($user, $post));
    }

    /** @test */
    public function user_cannot_view_deleted_post_unless_author_or_admin()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $post->delete();

        // Regular user cannot view deleted post
        $this->assertFalse($this->policy->view($user, $post));

        // Author can view deleted post
        $this->assertTrue($this->policy->view($author, $post));

        // Admin can view deleted post
        $admin = User::factory()->create(['username' => 'admin']);
        $this->assertTrue($this->policy->view($admin, $post));
    }

    /** @test */
    public function user_cannot_view_sensitive_content_if_underage()
    {
        $adult = User::factory()->create();
        $child = User::factory()->underage()->create();
        
        $post = Post::factory()->create(['is_sensitive' => true]);

        $this->assertTrue($this->policy->view($adult, $post));
        $this->assertFalse($this->policy->view($child, $post));
    }

    /** @test */
    public function private_user_posts_only_visible_to_approved_followers()
    {
        $privateUser = User::factory()->create(['is_private' => true]);
        $follower = User::factory()->create();
        $nonFollower = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $privateUser->id]);

        // Non-follower cannot view private user's post
        $this->assertFalse($this->policy->view($nonFollower, $post));

        // Follower without approval cannot view
        $this->assertFalse($this->policy->view($follower, $post));

        // Create approved follow
        \App\Models\Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $privateUser->id,
            'approved_at' => now(),
        ]);

        // Now follower can view
        $this->assertTrue($this->policy->view($follower, $post));
    }

    /** @test */
    public function user_can_create_post_if_not_banned()
    {
        $normalUser = User::factory()->create(['is_banned' => false]);
        $bannedUser = User::factory()->create(['is_banned' => true]);

        $this->assertTrue($this->policy->create($normalUser));
        $this->assertFalse($this->policy->create($bannedUser));
    }

    /** @test */
    public function only_author_can_update_post()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->update($author, $post));
        $this->assertFalse($this->policy->update($otherUser, $post));
    }

    /** @test */
    public function author_or_admin_can_delete_post()
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->create(['username' => 'admin']);
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->delete($author, $post));
        $this->assertFalse($this->policy->delete($otherUser, $post));
        $this->assertTrue($this->policy->delete($admin, $post));
    }

    /** @test */
    public function user_cannot_like_own_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertFalse($this->policy->like($user, $post));
    }

    /** @test */
    public function banned_user_cannot_like_post()
    {
        $user = User::factory()->create(['is_banned' => true]);
        $post = Post::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->assertFalse($this->policy->like($user, $post));
    }

    /** @test */
    public function underage_user_cannot_create_sensitive_content()
    {
        $adult = User::factory()->create();
        $child = User::factory()->underage()->create();

        $this->assertTrue($this->policy->createSensitiveContent($adult));
        $this->assertFalse($this->policy->createSensitiveContent($child));
    }
}