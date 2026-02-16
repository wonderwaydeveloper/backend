<?php

namespace App\Observers;

use App\Models\PremiumSubscription;
use Illuminate\Support\Facades\DB;

class PremiumSubscriptionObserver
{
    public function created(PremiumSubscription $subscription): void
    {
        $user = $subscription->user;
        
        if ($user && $subscription->status === 'active') {
            // Assign premium role
            if (!$user->hasRole('premium')) {
                $user->assignRole('premium');
            }
            
            // Update is_premium flag
            DB::table('users')->where('id', $user->id)->update([
                'is_premium' => true,
            ]);
        }
    }

    public function updated(PremiumSubscription $subscription): void
    {
        $user = $subscription->user;
        
        if (!$user) {
            return;
        }

        // If subscription cancelled or expired
        if ($subscription->status === 'cancelled' || $subscription->isExpired()) {
            // Remove premium role
            if ($user->hasRole('premium')) {
                $user->removeRole('premium');
            }
            
            // Update is_premium flag
            DB::table('users')->where('id', $user->id)->update([
                'is_premium' => false,
            ]);
        }
        
        // If subscription reactivated
        if ($subscription->status === 'active' && !$subscription->isExpired()) {
            if (!$user->hasRole('premium')) {
                $user->assignRole('premium');
            }
            
            DB::table('users')->where('id', $user->id)->update([
                'is_premium' => true,
            ]);
        }
    }
}
