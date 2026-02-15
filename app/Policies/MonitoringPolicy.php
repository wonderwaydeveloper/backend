<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MonitoringPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('monitoring.view');
    }

    public function viewErrors(User $user): bool
    {
        return $user->hasPermissionTo('monitoring.errors');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('monitoring.manage');
    }
}
