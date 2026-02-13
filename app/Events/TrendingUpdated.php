<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrendingUpdated
{
    use Dispatchable, SerializesModels;

    public $type;
    public $count;

    public function __construct($type, $count)
    {
        $this->type = $type;
        $this->count = $count;
    }
}
