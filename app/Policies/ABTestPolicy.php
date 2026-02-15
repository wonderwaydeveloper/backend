<?php

namespace App\Policies;

use App\Models\{ABTest, User};

class ABTestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('abtest.view');
    }

    public function view(User $user, ABTest $test): bool
    {
        return $user->hasPermissionTo('abtest.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('abtest.create');
    }

    public function manage(User $user, ABTest $test): bool
    {
        return $user->hasPermissionTo('abtest.manage');
    }

    public function delete(User $user, ABTest $test): bool
    {
        return $user->hasPermissionTo('abtest.delete');
    }
}
