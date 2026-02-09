<?php

namespace App\Providers;

use App\Events\CommentCreated;
use App\Events\PostLiked;
use App\Events\PostReposted;
use App\Events\UserFollowed;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendFollowNotification;
use App\Listeners\SendLikeNotification;
use App\Listeners\SendRepostNotification;
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
        \Illuminate\Support\Facades\Gate::policy(\App\Models\AuditLog::class, \App\Policies\AuditLogPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\User::class, \App\Policies\UserPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Message::class, \App\Policies\MessagePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Bookmark::class, \App\Policies\BookmarkPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Report::class, \App\Policies\ReportPolicy::class);
    }
}
