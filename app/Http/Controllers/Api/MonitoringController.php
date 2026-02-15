<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonitoringResource;
use App\Services\AdvancedMonitoringService;
use App\Services\ErrorTrackingService;
use Illuminate\Http\JsonResponse;

class MonitoringController extends Controller
{
    public function __construct(
        private AdvancedMonitoringService $monitoringService,
        private ErrorTrackingService $errorTrackingService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPolicy::class);

        return response()->json([
            'system_metrics' => $this->monitoringService->getSystemMetrics(),
            'error_stats' => $this->errorTrackingService->getTopErrors(),
            'status' => 'healthy'
        ]);
    }

    public function metrics(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPolicy::class);

        return response()->json($this->monitoringService->getSystemMetrics());
    }

    public function errors(): JsonResponse
    {
        $this->authorize('viewErrors', MonitoringPolicy::class);

        return response()->json([
            'top_errors' => $this->errorTrackingService->getTopErrors(),
            'error_stats' => $this->errorTrackingService->getErrorStats(),
        ]);
    }

    public function performance(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPolicy::class);

        return response()->json([
            'database' => $this->monitoringService->getDatabaseMetrics(),
            'cache' => $this->monitoringService->getCacheMetrics(),
            'memory' => $this->monitoringService->getMemoryMetrics(),
        ]);
    }
    
    public function cache(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPolicy::class);

        return response()->json([
            'cluster_info' => $this->monitoringService->getCacheMetrics(),
            'node_health' => 'healthy',
            'cache_stats' => $this->monitoringService->getCacheMetrics(),
        ]);
    }
    
    public function queue(): JsonResponse
    {
        $this->authorize('viewAny', MonitoringPolicy::class);

        return response()->json([
            'stats' => $this->monitoringService->getQueueMetrics(),
            'failed_jobs' => 0,
        ]);
    }
}