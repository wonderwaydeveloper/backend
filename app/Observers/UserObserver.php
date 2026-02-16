<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function created(User $user): void
    {
        // Assign default 'user' role to new users
        if (!$user->hasAnyRole(['user', 'verified', 'premium', 'organization', 'moderator', 'admin'])) {
            $user->assignRole('user');
        }
    }

    public function updated(User $user): void
    {
        // Handle email verification - check if email was just verified
        if ($user->wasChanged('email_verified_at')) {
            $original = $user->getOriginal('email_verified_at');
            
            if ($original === null && $user->email_verified_at !== null) {
                if ($user->hasRole('user') && !$user->hasRole('verified')) {
                    $user->removeRole('user');
                    $user->assignRole('verified');
                }
            }
        }
        
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