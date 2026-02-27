<?php

namespace App\Events;

use App\Models\CommunityNote;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityNoteApproved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CommunityNote $note)
    {
    }
}
