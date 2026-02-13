<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentIndexed
{
    use Dispatchable, SerializesModels;

    public $model;
    public $type;

    public function __construct($model, $type)
    {
        $this->model = $model;
        $this->type = $type;
    }
}
