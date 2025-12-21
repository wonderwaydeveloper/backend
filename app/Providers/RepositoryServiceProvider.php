<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\PostServiceInterface;
use App\Contracts\PostRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Services\PostService;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Repository Interfaces
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        
        // Bind Service Interfaces
        $this->app->bind(PostServiceInterface::class, PostService::class);
        
        // Singleton Services
        $this->app->singleton(\App\Services\SecurityEventLogger::class);
        $this->app->singleton(\App\Services\DataEncryptionService::class);
    }

    public function boot(): void
    {
        //
    }
}