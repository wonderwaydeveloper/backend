<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Conversation;
use App\Notifications\NewLikeNotification;
use App\Notifications\NewCommentNotification;
use App\Notifications\NewFollowerNotification;
use App\Notifications\FollowRequestNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class NotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
    }

    /** @test */
    public function new_like_notification_has_correct_data()
    {
        $liker = User::factory()->create();
        $post = Post::factory()->create();
        
        $notification = new NewLikeNotification($liker, $post);
        
        $array = $notification->toArray($post->user);
        
        $this->assertEquals('new_like', $array['type']);
        $this->assertEquals($liker->id, $array['liker_id']);
        $this->assertEquals($liker->name, $array['liker_name']);
        $this->assertEquals(get_class($post), $array['likeable_type']);
        $this->assertEquals($post->id, $array['likeable_id']);
    }

    /** @test */
    public function new_comment_notification_has_correct_data()
    {
        $commenter = User::factory()->create();
        $post = Post::factory()->create();
        
        $notification = new NewCommentNotification($commenter, $post);
        
        $array = $notification->toArray($post->user);
        
        $this->assertEquals('new_comment', $array['type']);
        $this->assertEquals($commenter->id, $array['commenter_id']);
        $this->assertEquals($commenter->name, $array['commenter_name']);
        $this->assertEquals(get_class($post), $array['commentable_type']);
        $this->assertEquals($post->id, $array['commentable_id']);
    }

    /** @test */
    public function new_follower_notification_has_correct_data()
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();
        
        $notification = new NewFollowerNotification($follower);
        
        $array = $notification->toArray($followed);
        
        $this->assertEquals('new_follower', $array['type']);
        $this->assertEquals($follower->id, $array['follower_id']);
        $this->assertEquals($follower->name, $array['follower_name']);
    }

    /** @test */
    public function follow_request_notification_has_correct_data()
    {
        $requester = User::factory()->create(['is_private' => true]);
        $requested = User::factory()->create();
        
        $notification = new FollowRequestNotification($requester);
        
        $array = $notification->toArray($requested);
        
        $this->assertEquals('follow_request', $array['type']);
        $this->assertEquals($requester->id, $array['requester_id']);
        $this->assertEquals($requester->name, $array['requester_name']);
    }

    /** @test */
    public function notifications_implement_should_queue_interface()
    {
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new NewLikeNotification(User::factory()->make(), Post::factory()->make())
        );
        
        $this->assertInstanceOf(
            \Illuminate\Contracts\Queue\ShouldQueue::class,
            new NewCommentNotification(User::factory()->make(), Post::factory()->make())
        );
    }
}