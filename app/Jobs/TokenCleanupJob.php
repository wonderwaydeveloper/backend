<?php

namespace App\Jobs;

use App\Services\TokenManagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TokenCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function handle(TokenManagementService $tokenService): void
    {
        try {
            $stats = $tokenService->cleanupExpiredTokens();
            
            $totalCleaned = array_sum($stats);
            
            if ($totalCleaned > 0) {
                Log::info('Token cleanup completed', [
                    'stats' => $stats,
                    'total_cleaned' => $totalCleaned
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Token cleanup job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Token cleanup job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}