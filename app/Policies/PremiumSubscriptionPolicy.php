<?php

namespace App\Policies;

use App\Models\PremiumSubscription;
use App\Models\User;

class PremiumSubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('premium.view', 'sanctum');
    }

    public function view(User $user, PremiumSubscription $subscription): bool
    {
        return $user->hasPermissionTo('premium.view', 'sanctum')
            && $subscription->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('premium.subscribe', 'sanctum');
    }

    public function cancel(User $user, PremiumSubscription $subscription): bool
    {
        return $user->hasPermissionTo('premium.cancel', 'sanctum')
            && $subscription->user_id === $user->id;
    }
}
