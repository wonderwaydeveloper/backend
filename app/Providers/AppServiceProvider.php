<?php

namespace App\Providers;

use App\Events\PostLiked;
use App\Events\PostReposted;
use App\Events\UserFollowed;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PostLiked::class, SendLikeNotification::class);
        Event::listen(UserFollowed::class, SendFollowNotification::class);
        Event::listen(PostReposted::class, SendRepostNotification::class);

        \App\Models\Post::observe(\App\Observers\PostObserver::class);
    }
}
