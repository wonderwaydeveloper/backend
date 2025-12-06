<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ثبت NotificationService
        $this->app->singleton(\App\Services\NotificationService::class);
        
        // ثبت RedisService
        $this->app->singleton(\App\Services\RedisService::class);
        
        // ثبت UserService با NotificationService
        $this->app->singleton(\App\Services\UserService::class, function ($app) {
            return new \App\Services\UserService(
                $app->make(\App\Services\NotificationService::class)
            );
        });
        
        // ثبت PostService با وابستگی‌ها
        $this->app->singleton(\App\Services\PostService::class, function ($app) {
            return new \App\Services\PostService(
                $app->make(\App\Services\RedisService::class),
                $app->make(\App\Services\NotificationService::class)
            );
        });
        
        // ثبت CommentService با NotificationService
        $this->app->singleton(\App\Services\CommentService::class, function ($app) {
            return new \App\Services\CommentService(
                $app->make(\App\Services\NotificationService::class)
            );
        });
        
        // ثبت ArticleService با NotificationService
        $this->app->singleton(\App\Services\ArticleService::class, function ($app) {
            return new \App\Services\ArticleService(
                $app->make(\App\Services\NotificationService::class)
            );
        });

        // سایر سرویس‌ها
        $this->app->singleton(\App\Services\AuthService::class);
        $this->app->singleton(\App\Services\EmailVerificationService::class);
        $this->app->singleton(\App\Services\PhoneVerificationService::class);
        $this->app->singleton(\App\Services\TwoFactorService::class);
        $this->app->singleton(\App\Services\ParentalControlService::class);
        $this->app->singleton(\App\Services\PrivateMessageService::class);
        $this->app->singleton(\App\Services\BookmarkService::class);
        $this->app->singleton(\App\Services\SearchService::class);
        $this->app->singleton(\App\Services\AdminService::class);
    }

    public function boot(): void
    {
        //
    }
}