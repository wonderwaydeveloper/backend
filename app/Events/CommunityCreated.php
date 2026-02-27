<?php

namespace App\Events;

use App\Models\Community;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Community $community)
    {
    }
}
