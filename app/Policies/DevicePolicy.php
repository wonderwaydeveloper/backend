<?php

namespace App\Policies;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('device.view');
    }

    public function view(User $user, DeviceToken $device): bool
    {
        return $user->id === $device->user_id && $user->hasPermissionTo('device.view');
    }

    public function register(User $user): bool
    {
        return $user->hasPermissionTo('device.register');
    }

    public function trust(User $user, DeviceToken $device): bool
    {
        return $user->id === $device->user_id && $user->hasPermissionTo('device.trust');
    }

    public function revoke(User $user, DeviceToken $device): bool
    {
        return $user->id === $device->user_id && $user->hasPermissionTo('device.revoke');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('device.manage');
    }
}
