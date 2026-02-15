<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnalyticsTracked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $eventType,
        public string $entityType,
        public int $entityId,
        public ?int $userId = null
    ) {}
}
