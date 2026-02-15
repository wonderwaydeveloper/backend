<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class AnalyticsPolicy
{
    public function viewUserAnalytics(User $user): bool
    {
        return $user->hasPermissionTo('analytics.view');
    }

    public function viewPostAnalytics(User $user, Post $post): bool
    {
        return $user->id === $post->user_id && $user->hasPermissionTo('analytics.view');
    }

    public function trackEvent(?User $user): bool
    {
        return true; // Public tracking allowed
    }
}
