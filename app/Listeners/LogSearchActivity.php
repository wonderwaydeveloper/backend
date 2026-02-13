<?php

namespace App\Listeners;

use App\Events\SearchPerformed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogSearchActivity implements ShouldQueue
{
    public function handle(SearchPerformed $event): void
    {
        Log::info('Search performed', [
            'user_id' => $event->userId,
            'query' => $event->query,
            'type' => $event->type,
            'results_count' => $event->resultsCount,
            'timestamp' => now(),
        ]);
    }
}
