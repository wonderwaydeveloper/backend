<?php

namespace App\Providers;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Repositories\NotificationRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        
        // Service bindings
        $this->app->singleton(\App\Services\UserService::class);
        $this->app->singleton(\App\Services\PostService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
