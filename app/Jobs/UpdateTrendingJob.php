<?php

namespace App\Jobs;

use App\Events\TrendingUpdated;
use App\Services\TrendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateTrendingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(TrendingService $trendingService): void
    {
        $result = $trendingService->updateTrendingScores();
        
        event(new TrendingUpdated('all', $result));
    }
}
