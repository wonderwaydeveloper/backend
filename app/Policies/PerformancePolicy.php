<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformancePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('performance.view');
    }

    public function optimize(User $user): bool
    {
        return $user->hasPermissionTo('performance.optimize');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('performance.manage');
    }
}
