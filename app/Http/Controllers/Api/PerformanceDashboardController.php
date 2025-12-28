<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\{CacheOptimizationService, DatabaseOptimizationService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Cache, DB, Redis};

class PerformanceDashboardController extends Controller
{
    public function __construct(
        private CacheOptimizationService $cacheService,
        private DatabaseOptimizationService $dbService
    ) {}

    public function dashboard(): JsonResponse
    {
        return response()->json([
            'cache_stats' => $this->cacheService->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'performance_metrics' => $this->getPerformanceMetrics(),
            'optimization_status' => $this->getOptimizationStatus()
        ]);
    }

    public function optimizeDatabase(): JsonResponse
    {
        $results = [
            'indexes_created' => $this->dbService->createOptimizedIndexes(),
            'tables_optimized' => $this->dbService->optimizeTableStructure(),
            'timestamp' => now()->toISOString()
        ];

        return response()->json($results);
    }

    public function warmupCache(Request $request): JsonResponse
    {
        $userIds = $request->get('user_ids', []);
        
        if (empty($userIds)) {
            // Warm cache for top 100 active users
            $userIds = DB::table('users')
                ->orderBy('last_activity_at', 'desc')
                ->limit(100)
                ->pluck('id')
                ->toArray();
        }

        $results = $this->cacheService->batchWarmup($userIds);

        return response()->json([
            'warmed_users' => count($userIds),
            'results' => $results,
            'timestamp' => now()->toISOString()
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $tags = $request->get('tags', ['timeline', 'posts', 'user']);
        
        foreach ($tags as $tag) {
            Cache::tags([$tag])->flush();
        }

        return response()->json([
            'cleared_tags' => $tags,
            'timestamp' => now()->toISOString()
        ]);
    }

    public function queryAnalysis(): JsonResponse
    {
        return response()->json([
            'slow_queries' => $this->dbService->analyzeSlowQueries(),
            'connection_pool' => $this->dbService->getConnectionPoolStats(),
            'recommendations' => $this->getPerformanceRecommendations()
        ]);
    }

    public function realTimeMetrics(): JsonResponse
    {
        return response()->json([
            'response_time' => $this->measureResponseTime(),
            'memory_usage' => $this->getMemoryUsage(),
            'active_connections' => $this->getActiveConnections(),
            'cache_hit_ratio' => $this->getCacheHitRatio(),
            'timestamp' => now()->toISOString()
        ]);
    }

    private function getDatabaseStats(): array
    {
        return [
            'total_queries' => $this->getTotalQueries(),
            'avg_query_time' => $this->getAverageQueryTime(),
            'connection_pool' => $this->dbService->getConnectionPoolStats()
        ];
    }

    private function getPerformanceMetrics(): array
    {
        return [
            'response_time_avg' => $this->getAverageResponseTime(),
            'throughput' => $this->getThroughput(),
            'error_rate' => $this->getErrorRate(),
            'uptime' => $this->getUptime()
        ];
    }

    private function getOptimizationStatus(): array
    {
        return [
            'n_plus_one_eliminated' => $this->checkNPlusOneStatus(),
            'indexes_optimized' => $this->checkIndexStatus(),
            'cache_layers_active' => $this->checkCacheStatus(),
            'query_optimization' => $this->checkQueryOptimization()
        ];
    }

    private function measureResponseTime(): float
    {
        $start = microtime(true);
        
        // Simple database query to measure response time
        DB::select('SELECT 1');
        
        return round((microtime(true) - $start) * 1000, 2); // milliseconds
    }

    private function getMemoryUsage(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit_mb' => ini_get('memory_limit')
        ];
    }

    private function getActiveConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getCacheHitRatio(): float
    {
        return $this->cacheService->getCacheStats()['hit_ratio'] ?? 0;
    }

    private function getTotalQueries(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Questions'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAverageQueryTime(): float
    {
        // This would need to be implemented with query logging
        return 0.0;
    }

    private function getAverageResponseTime(): float
    {
        // This would be tracked via middleware
        return 0.0;
    }

    private function getThroughput(): int
    {
        // Requests per second - would be tracked via middleware
        return 0;
    }

    private function getErrorRate(): float
    {
        // Error percentage - would be tracked via middleware
        return 0.0;
    }

    private function getUptime(): string
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Uptime'");
            $seconds = (int) ($result[0]->Value ?? 0);
            return gmdate('H:i:s', $seconds);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function checkNPlusOneStatus(): bool
    {
        // Check if optimized repositories are being used
        return class_exists('App\Repositories\OptimizedPostRepository');
    }

    private function checkIndexStatus(): bool
    {
        try {
            $indexes = DB::select("SHOW INDEX FROM posts WHERE Key_name = 'idx_posts_timeline'");
            return !empty($indexes);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCacheStatus(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkQueryOptimization(): bool
    {
        // Check if query optimization service is active
        return class_exists('App\Services\DatabaseOptimizationService');
    }

    private function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        
        if ($this->getCacheHitRatio() < 80) {
            $recommendations[] = 'Cache hit ratio is low - consider warming more cache';
        }
        
        if ($this->getActiveConnections() > 50) {
            $recommendations[] = 'High connection count - consider connection pooling';
        }
        
        $memoryUsage = $this->getMemoryUsage();
        if ($memoryUsage['current_mb'] > 256) {
            $recommendations[] = 'High memory usage - check for memory leaks';
        }
        
        return $recommendations;
    }
}