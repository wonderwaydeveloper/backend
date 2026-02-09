<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $muter,
        public User $muted
    ) {}
}