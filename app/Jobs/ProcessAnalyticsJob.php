<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $eventType,
        public string $entityType,
        public int $entityId,
        public ?int $userId,
        public array $metadata = []
    ) {}

    public function handle(): void
    {
        AnalyticsEvent::track(
            $this->eventType,
            $this->entityType,
            $this->entityId,
            $this->userId,
            $this->metadata
        );
    }
}
