<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PerformanceResource;
use App\Services\CacheManagementService;
use App\Services\DatabaseOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceController extends Controller
{
    public function __construct(
        private CacheManagementService $cacheService,
        private DatabaseOptimizationService $dbService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', PerformancePolicy::class);

        return response()->json([
            'cache' => $this->cacheService->getCacheStats(),
            'database' => $this->getDatabaseStats(),
            'performance' => $this->getPerformanceMetrics(),
            'optimization_status' => $this->getOptimizationStatus()
        ]);
    }

    public function optimizeTimeline(Request $request): JsonResponse
    {
        $this->authorize('optimize', PerformancePolicy::class);

        $user = $request->user();
        $posts = $this->dbService->optimizeTimeline($user->id);

        return response()->json([
            'posts' => $posts,
            'cached' => true,
            'performance' => 'optimized',
        ]);
    }

    public function optimize(Request $request): JsonResponse
    {
        $this->authorize('optimize', PerformancePolicy::class);

        $type = $request->input('type', 'all');
        $results = [];

        if (in_array($type, ['all', 'cache'])) {
            $results['cache'] = $this->cacheService->warmupCache();
        }

        if (in_array($type, ['all', 'database'])) {
            $results['database'] = $this->dbService->createOptimizedIndexes();
        }

        if (in_array($type, ['all', 'timeline'])) {
            $results['timeline'] = $this->dbService->optimizeTimeline($request->user()->id ?? null);
        }

        return response()->json($results);
    }

    public function warmupCache(): JsonResponse
    {
        $this->authorize('manage', PerformancePolicy::class);

        $this->cacheService->warmupCache();

        return response()->json([
            'message' => 'Cache warmed up successfully',
            'timestamp' => now(),
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $this->authorize('manage', PerformancePolicy::class);

        $type = $request->input('type', 'all');

        switch ($type) {
            case 'user':
                $userId = $request->input('user_id');
                $this->cacheService->invalidateUserCache($userId);
                break;
            case 'posts':
                Cache::tags(['posts'])->flush();
                break;
            case 'all':
                Cache::flush();
                break;
        }

        return response()->json([
            'message' => "Cache cleared: {$type}",
            'timestamp' => now(),
        ]);
    }

    public function realTimeMetrics(): JsonResponse
    {
        $this->authorize('viewAny', PerformancePolicy::class);

        $data = [
            'response_time' => $this->measureResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'active_connections' => $this->getActiveConnections(),
            'cache_hit_rate' => $this->cacheService->getCacheStats()['hit_ratio'] ?? 0,
            'timestamp' => now()
        ];

        return response()->json([
            'data' => new PerformanceResource($data)
        ]);
    }

    private function getDatabaseStats(): array
    {
        try {
            $stats = DB::select("
                SELECT table_name, table_rows,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY size_mb DESC LIMIT 10
            ");

            return [
                'tables' => $stats,
                'connections' => DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Database stats unavailable'];
        }
    }

    private function getPerformanceMetrics(): array
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);

        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $startTime,
            'queries_count' => 0,
        ];
    }

    private function getOptimizationStatus(): array
    {
        return [
            'cache_active' => Cache::getStore() instanceof \Illuminate\Cache\RedisStore,
            'indexes_optimized' => $this->checkIndexes(),
            'query_optimization' => class_exists('App\\Services\\DatabaseOptimizationService')
        ];
    }

    private function measureResponseTime(): float
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2);
    }

    private function getMemoryUsage(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
    }

    private function getActiveConnections(): int
    {
        try {
            return (int) DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function checkIndexes(): bool
    {
        try {
            return !empty(DB::select("SHOW INDEX FROM posts WHERE Key_name = 'idx_posts_timeline'"));
        } catch (\Exception $e) {
            return false;
        }
    }
}
