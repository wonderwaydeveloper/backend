<?php

namespace App\Policies;

use App\Models\User;

class SearchPolicy
{
    public function search(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        
        return $user->email_verified_at !== null && 
               ($user->hasPermissionTo('search.basic') || !\Schema::hasTable('permissions'));
    }

    public function advanced(?User $user): bool
    {
        if (!$user || !$user->email_verified_at) {
            return false;
        }
        
        try {
            return $user->hasPermissionTo('search.advanced');
        } catch (\Exception $e) {
            return false;
        }
    }

    public function viewTrending(?User $user): bool
    {
        return true;
    }
}
