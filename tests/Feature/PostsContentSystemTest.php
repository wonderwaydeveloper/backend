<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Post, Comment};
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostsContentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->givePermissionTo([
            'post.create', 'post.edit.own', 'post.delete.own', 'post.like', 
            'post.repost', 'post.bookmark', 'post.schedule',
            'comment.create'
        ]);
    }

    // PostController - 14 endpoints
    public function test_can_list_posts(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/posts')->assertStatus(200);
    }

    public function test_can_create_post(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/posts', ['content' => 'Test'])
            ->assertStatus(201);
    }

    public function test_can_show_post(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}")->assertStatus(200);
    }

    public function test_can_update_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/posts/{$post->id}", ['content' => 'Updated'])
            ->assertStatus(200);
    }

    public function test_can_delete_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/posts/{$post->id}")->assertStatus(200);
    }

    public function test_can_like_post(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->postJson("/api/posts/{$post->id}/like")->assertStatus(200);
    }

    public function test_can_unlike_post(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/posts/{$post->id}/like")->assertStatus(200);
    }

    public function test_can_list_post_likes(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/likes")->assertStatus(200);
    }

    public function test_can_quote_post(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/quote", ['content' => 'Quote'])
            ->assertStatus(201);
    }

    public function test_can_list_post_quotes(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/quotes")->assertStatus(200);
    }

    public function test_can_publish_draft(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id, 'is_draft' => true]);
        $this->actingAs($this->user, 'sanctum')->postJson("/api/posts/{$post->id}/publish")->assertStatus(200);
    }

    public function test_can_view_post_edit_history(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/edit-history")->assertStatus(200);
    }

    public function test_can_view_timeline(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/timeline')->assertStatus(200);
    }

    public function test_can_list_drafts(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/drafts')->assertStatus(200);
    }

    // CommentController - 4 endpoints
    public function test_can_list_comments(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/comments")->assertStatus(200);
    }

    public function test_can_create_comment(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/comments", ['content' => 'Comment'])
            ->assertStatus(201);
    }

    public function test_can_delete_comment(): void
    {
        $comment = Comment::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/comments/{$comment->id}")->assertStatus(200);
    }

    public function test_can_like_comment(): void
    {
        $comment = Comment::factory()->create();
        $this->actingAs($this->user, 'sanctum')->postJson("/api/comments/{$comment->id}/like")->assertStatus(200);
    }

    // BookmarkController - 2 endpoints
    public function test_can_list_bookmarks(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/bookmarks')->assertStatus(200);
    }

    public function test_can_toggle_bookmark(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->postJson("/api/posts/{$post->id}/bookmark")->assertStatus(200);
    }

    // RepostController - 4 endpoints
    public function test_can_repost(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->postJson("/api/posts/{$post->id}/repost")->assertStatus(201);
    }

    public function test_can_unrepost(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->postJson("/api/posts/{$post->id}/repost");
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/posts/{$post->id}/repost")->assertStatus(200);
    }

    public function test_can_list_post_reposts(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/reposts")->assertStatus(200);
    }

    public function test_can_list_my_reposts(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/my-reposts')->assertStatus(200);
    }

    // ThreadController - 4 endpoints
    public function test_can_create_thread(): void
    {
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/threads', ['posts' => [['content' => 'T1'], ['content' => 'T2']]])
            ->assertStatus(201);
    }

    public function test_can_show_thread(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/threads/{$post->id}")->assertStatus(200);
    }

    public function test_can_add_to_thread(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/threads/{$post->id}/add", ['content' => 'Add'])
            ->assertStatus(201);
    }

    public function test_can_view_thread_stats(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/threads/{$post->id}/stats")->assertStatus(200);
    }

    // ScheduledPostController - 3 endpoints
    public function test_can_create_scheduled_post(): void
    {
        $this->user->assignRole('premium');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/scheduled-posts', [
                'content' => 'Scheduled',
                'scheduled_at' => now()->addHour()->toDateTimeString()
            ])
            ->assertStatus(201);
    }

    public function test_can_list_scheduled_posts(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/scheduled-posts')->assertStatus(200);
    }

    public function test_can_delete_scheduled_post(): void
    {
        $scheduled = \App\Models\ScheduledPost::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/scheduled-posts/{$scheduled->id}")->assertStatus(200);
    }

    // PollController - 4 endpoints
    public function test_can_create_poll(): void
    {
        $this->user->givePermissionTo('poll.create');
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/polls', [
                'post_id' => $post->id,
                'question' => 'Test poll?',
                'options' => ['Option 1', 'Option 2'],
                'duration_hours' => 24
            ])
            ->assertStatus(201);
    }

    public function test_can_vote_on_poll(): void
    {
        $this->user->givePermissionTo('poll.vote');
        $post = Post::factory()->create();
        $poll = \App\Models\Poll::factory()->create(['post_id' => $post->id]);
        $poll->options()->create(['text' => 'Option 1', 'votes_count' => 0]);
        $option = $poll->options()->first();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/polls/{$poll->id}/vote/{$option->id}")
            ->assertStatus(200);
    }

    public function test_can_view_poll_results(): void
    {
        $post = Post::factory()->create();
        $poll = \App\Models\Poll::factory()->create(['post_id' => $post->id]);
        $this->actingAs($this->user, 'sanctum')->getJson("/api/polls/{$poll->id}/results")->assertStatus(200);
    }

    public function test_can_delete_poll(): void
    {
        $this->user->givePermissionTo('poll.delete.own');
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $poll = \App\Models\Poll::factory()->create(['post_id' => $post->id]);
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/polls/{$poll->id}")->assertStatus(200);
    }

    // MediaController - 7 endpoints
    public function test_can_list_media(): void
    {
        $this->user->givePermissionTo('media.view');
        $this->actingAs($this->user, 'sanctum')->getJson('/api/media')->assertStatus(200);
    }

    public function test_can_show_media(): void
    {
        $this->user->givePermissionTo('media.view');
        $media = \App\Models\Media::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->getJson("/api/media/{$media->id}")->assertStatus(200);
    }

    public function test_can_upload_image(): void
    {
        $this->user->assignRole('premium');
        $this->user->givePermissionTo(['media.view', 'media.upload']);
        $file = \Illuminate\Http\UploadedFile::fake()->image('test.jpg');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/image', ['image' => $file])
            ->assertStatus(201);
    }

    public function test_can_upload_video(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->user->assignRole('premium');
        $this->user->givePermissionTo(['media.view', 'media.upload']);
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.mp4', 1024, 'video/mp4');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/video', ['video' => $file])
            ->assertStatus(201);
    }

    public function test_can_upload_document(): void
    {
        $this->user->givePermissionTo('media.upload');
        $file = \Illuminate\Http\UploadedFile::fake()->create('test.pdf', 1024, 'application/pdf');
        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/media/upload/document', ['document' => $file])
            ->assertStatus(201);
    }

    public function test_can_delete_media(): void
    {
        $this->user->givePermissionTo('media.delete');
        $media = \App\Models\Media::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->deleteJson("/api/media/{$media->id}")->assertStatus(200);
    }

    public function test_can_check_media_status(): void
    {
        $this->user->givePermissionTo('media.view');
        $media = \App\Models\Media::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'sanctum')->getJson("/api/media/{$media->id}/status")->assertStatus(200);
    }

    // CommunityNoteController - 4 endpoints
    public function test_can_list_community_notes(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')->getJson("/api/posts/{$post->id}/community-notes")->assertStatus(200);
    }

    public function test_can_create_community_note(): void
    {
        $post = Post::factory()->create();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/posts/{$post->id}/community-notes", ['content' => 'This is a community note with enough characters'])
            ->assertStatus(201);
    }

    public function test_can_vote_on_community_note(): void
    {
        $note = \App\Models\CommunityNote::factory()->create();
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/community-notes/{$note->id}/vote", ['vote_type' => 'helpful'])
            ->assertStatus(200);
    }

    public function test_can_list_pending_community_notes(): void
    {
        $this->actingAs($this->user, 'sanctum')->getJson('/api/community-notes/pending')->assertStatus(200);
    }

    // VideoController - removed (merged into MediaController)
}
