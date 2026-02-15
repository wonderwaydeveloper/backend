<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AutoScalingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('autoscaling.view');
    }

    public function predict(User $user): bool
    {
        return $user->hasPermissionTo('autoscaling.predict');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('autoscaling.manage');
    }
}
