<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function updated(User $user): void
    {
        // Clear user cache
        Cache::forget("user.{$user->id}");
        Cache::forget("user.profile.{$user->id}");
        Cache::forget("user.suggestions.{$user->id}");
        
        // Clear followers/following cache
        Cache::forget("user.followers.{$user->id}");
        Cache::forget("user.following.{$user->id}");
    }

    public function deleted(User $user): void
    {
        // Clear all user-related cache
        Cache::forget("user.{$user->id}");
        Cache::forget("user.profile.{$user->id}");
        Cache::forget("user.suggestions.{$user->id}");
        Cache::forget("user.followers.{$user->id}");
        Cache::forget("user.following.{$user->id}");
    }
}