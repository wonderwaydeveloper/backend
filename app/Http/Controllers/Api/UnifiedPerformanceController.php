<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CacheOptimizationService;
use App\Services\DatabaseOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UnifiedPerformanceController extends Controller
{
    public function __construct(
        private CacheOptimizationService $cacheService,
        private DatabaseOptimizationService $dbService
    ) {}

    public function dashboard()
    {
        return response()->json([
            'cache' => $this->cacheService->getCacheStats(),
            'database' => $this->getDatabaseStats(),
            'performance' => $this->getPerformanceMetrics(),
            'optimization_status' => $this->getOptimizationStatus()
        ]);
    }

    public function optimize(Request $request)
    {
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

    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');

        switch ($type) {
            case 'user':
                $this->cacheService->invalidateUserCache($request->input('user_id'));
                break;
            case 'posts':
                Cache::tags(['posts'])->flush();
                break;
            default:
                Cache::flush();
        }

        return response()->json(['message' => "Cache cleared: {$type}"]);
    }

    public function realTimeMetrics()
    {
        return response()->json([
            'response_time' => $this->measureResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'active_connections' => $this->getActiveConnections(),
            'cache_hit_ratio' => $this->cacheService->getCacheStats()['hit_ratio'] ?? 0
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
                ORDER BY size_mb DESC LIMIT 5
            ");

            return [
                'tables' => $stats,
                'connections' => DB::select("SHOW STATUS LIKE 'Threads_connected'")[0]->Value ?? 0
            ];
        } catch (\Exception $e) {
            return ['error' => 'Database stats unavailable'];
        }
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true))
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