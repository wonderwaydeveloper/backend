<?php

namespace Tests\Feature\Notifications;

use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use App\Notifications\NewLikeNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;

class MailNotificationsTest extends TestCase
{
    #[Test]
    public function like_notification_sends_email()
    {
        Notification::fake();
        
        $user = User::factory()->create();
        $liker = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        
        // ارسال نوتیفیکیشن
        $user->notify(new NewLikeNotification($liker, $post));
        
        // بررسی اینکه نوتیفیکیشن ارسال شده
        Notification::assertSentTo(
            $user,
            NewLikeNotification::class,
            function ($notification) use ($liker, $post) {
                return $notification->liker->id === $liker->id &&
                       $notification->likeable->id === $post->id;
            }
        );
        
        // بررسی اینکه ایمیل هم ارسال شده
        Notification::assertSentTo(
            $user,
            NewLikeNotification::class,
            function ($notification, $channels) {
                return in_array('mail', $channels);
            }
        );
    }
}