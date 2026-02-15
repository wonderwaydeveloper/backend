<?php

namespace App\Policies;

use App\Models\User;
use App\Monetization\Models\Advertisement;

class AdvertisementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('advertisement.view', 'sanctum');
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('advertisement.view', 'sanctum');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('advertisement.create', 'sanctum');
    }

    public function manage(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('advertisement.manage', 'sanctum') 
            || $advertisement->advertiser_id === $user->id;
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermissionTo('advertisement.delete', 'sanctum')
            || $advertisement->advertiser_id === $user->id;
    }
}
