<?php

namespace App\Providers;

use App\Events\{CommentCreated, MessageSent, PostLiked, PostReposted, UserFollowed};
use App\Listeners\{SendCommentNotification, SendFollowNotification, SendLikeNotification, SendMessageNotification, SendRepostNotification};
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Authentication Services as Singletons
        $this->app->singleton(\App\Services\SessionTimeoutService::class);
        $this->app->singleton(\App\Services\TwoFactorService::class);
        $this->app->singleton(\App\Services\SmsService::class);
        
        // Register with proper dependencies
        $this->app->singleton(\App\Services\RateLimitingService::class, function ($app) {
            return new \App\Services\RateLimitingService(
                $app->make(\App\Services\AuditTrailService::class)
            );
        });
        
        $this->app->singleton(\App\Services\EmailService::class, function ($app) {
            return new \App\Services\EmailService(
                $app->make(\App\Services\AuditTrailService::class),
                $app->make(\App\Services\RateLimitingService::class)
            );
        });
        
        $this->app->singleton(\App\Services\AuditTrailService::class);
        
        $this->app->singleton(\App\Services\SecurityMonitoringService::class, function ($app) {
            return new \App\Services\SecurityMonitoringService(
                $app->make(\App\Services\AuditTrailService::class),
                $app->make(\App\Services\RateLimitingService::class)
            );
        });
        
        $this->app->bind(\App\Services\NotificationService::class, function ($app) {
            if ($app->environment('testing')) {
                return new \App\Services\NotificationService(null, null);
            }
            return new \App\Services\NotificationService(
                $app->make(\App\Services\EmailService::class),
                $app->make(\App\Services\PushNotificationService::class)
            );
        });

        // Register Space Repositories
        $this->app->bind(
            \App\Contracts\Repositories\SpaceRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentSpaceRepository::class
        );
        $this->app->bind(
            \App\Contracts\Repositories\SpaceParticipantRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentSpaceParticipantRepository::class
        );

        // Register List Repositories
        $this->app->bind(
            \App\Contracts\Repositories\ListRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentListRepository::class
        );
        $this->app->bind(
            \App\Contracts\Repositories\ListMemberRepositoryInterface::class,
            \App\Repositories\Eloquent\EloquentListMemberRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PostLiked::class, SendLikeNotification::class);
        Event::listen(UserFollowed::class, SendFollowNotification::class);
        Event::listen(PostReposted::class, SendRepostNotification::class);
        Event::listen(CommentCreated::class, SendCommentNotification::class);
        Event::listen(MessageSent::class, SendMessageNotification::class);

        // Space Events
        Event::listen(\App\Events\SpaceParticipantJoined::class, \App\Listeners\SendSpaceNotification::class);
        Event::listen(\App\Events\SpaceEnded::class, \App\Listeners\SendSpaceNotification::class);

        // List Events
        Event::listen(\App\Events\ListMemberAdded::class, \App\Listeners\SendListNotification::class);
        Event::listen(\App\Events\ListSubscribed::class, \App\Listeners\SendListNotification::class);

        // Poll Events
        Event::listen(\App\Events\PollVoted::class, \App\Listeners\SendPollNotification::class);

        // Mention Events
        Event::listen(\App\Events\UserMentioned::class, \App\Listeners\SendMentionNotification::class);

        // Real-time Events
        Event::listen(\App\Events\UserOnlineStatus::class, function($event) {
            // Event auto-broadcasts via ShouldBroadcast
        });

        \App\Models\Post::observe(\App\Observers\PostObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);

        // Register All Policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Post::class, \App\Policies\PostPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Comment::class, \App\Policies\CommentPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Community::class, \App\Policies\CommunityPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Moment::class, \App\Policies\MomentPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Notification::class, \App\Policies\NotificationPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\ScheduledPost::class, \App\Policies\ScheduledPostPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Space::class, \App\Policies\SpacePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\UserList::class, \App\Policies\UserListPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Poll::class, \App\Policies\PollPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Mention::class, \App\Policies\MentionPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Media::class, \App\Policies\MediaPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\AuditLog::class, \App\Policies\AuditLogPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Message::class, \App\Policies\MessagePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Bookmark::class, \App\Policies\BookmarkPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Report::class, \App\Policies\ReportPolicy::class);
    }
}
