<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ثبت سرویس‌های مورد نیاز
        $this->app->singleton(\App\Services\AuthService::class);
        $this->app->singleton(\App\Services\PostService::class);
        $this->app->singleton(\App\Services\UserService::class);
        $this->app->singleton(\App\Services\EmailVerificationService::class);
        $this->app->singleton(\App\Services\PhoneVerificationService::class);
    }

    public function boot(): void
    {
        //
    }
}