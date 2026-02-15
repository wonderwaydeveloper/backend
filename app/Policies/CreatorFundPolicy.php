<?php

namespace App\Policies;

use App\Models\User;
use App\Monetization\Models\CreatorFund;

class CreatorFundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('creatorfund.view', 'sanctum');
    }

    public function view(User $user, CreatorFund $creatorFund): bool
    {
        return $user->hasPermissionTo('creatorfund.view', 'sanctum')
            && $creatorFund->creator_id === $user->id;
    }

    public function requestPayout(User $user): bool
    {
        return $user->hasPermissionTo('creatorfund.payout', 'sanctum');
    }
}
