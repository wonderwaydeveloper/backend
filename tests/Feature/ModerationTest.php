<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
                'description' => 'This post contains spam content',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $user->id,
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);
    }

    public function test_user_can_report_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'comment',
                'reportable_id' => $comment->id,
                'reason' => 'harassment',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $user->id,
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'harassment',
        ]);
    }

    public function test_user_can_report_user(): void
    {
        $reporter = User::factory()->create();
        $reportedUser = User::factory()->create();

        $response = $this->actingAs($reporter, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'user',
                'reportable_id' => $reportedUser->id,
                'reason' => 'harassment',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => 'user',
            'reportable_id' => $reportedUser->id,
            'reason' => 'harassment',
        ]);
    }

    public function test_user_cannot_report_same_content_twice(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // First report
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'spam',
            ]);

        // Second report (should fail)
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'inappropriate',
            ]);

        $response->assertStatus(400);
    }

    public function test_report_requires_valid_reason(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/moderation/report', [
                'reportable_type' => 'post',
                'reportable_id' => $post->id,
                'reason' => 'invalid_reason',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_guest_cannot_report_content(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/api/moderation/report', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);

        $response->assertStatus(401);
    }
}
