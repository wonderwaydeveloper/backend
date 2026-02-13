<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SearchPerformed
{
    use Dispatchable, SerializesModels;

    public $userId;
    public $query;
    public $type;
    public $resultsCount;

    public function __construct($userId, $query, $type, $resultsCount)
    {
        $this->userId = $userId;
        $this->query = $query;
        $this->type = $type;
        $this->resultsCount = $resultsCount;
    }
}
