<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    public function view(User $user, Report $report): bool
    {
        // Own report or moderator/admin
        return $user->id === $report->user_id || 
               $user->hasAnyRole(['moderator', 'admin']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['moderator', 'admin']);
    }

    public function review(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['moderator', 'admin']);
    }

    public function resolve(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['moderator', 'admin']);
    }
}
