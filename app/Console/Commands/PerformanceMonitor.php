<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitor extends Command
{
    protected $signature = 'performance:monitor';
    protected $description = 'Monitor system performance metrics';

    public function handle()
    {
        $this->info('🔍 Performance Monitoring Started...');
        
        // Database Performance
        $this->checkDatabasePerformance();
        
        // Cache Performance
        $this->checkCachePerformance();
        
        // Memory Usage
        $this->checkMemoryUsage();
        
        // Response Time Test
        $this->checkResponseTime();
        
        $this->info('✅ Performance monitoring completed');
    }

    private function checkDatabasePerformance()
    {
        $this->info('📊 Database Performance:');
        
        $start = microtime(true);
        DB::table('posts')->count();
        $dbTime = (microtime(true) - $start) * 1000;
        
        $this->line("  - Query time: {$dbTime}ms");
        
        if ($dbTime > 50) {
            $this->warn("  ⚠️  Database query time exceeds 50ms");
        } else {
            $this->info("  ✅ Database performance OK");
        }
    }

    private function checkCachePerformance()
    {
        $this->info('🗄️  Cache Performance:');
        
        $start = microtime(true);
        Cache::put('test_key', 'test_value', 60);
        $writeTime = (microtime(true) - $start) * 1000;
        
        $start = microtime(true);
        Cache::get('test_key');
        $readTime = (microtime(true) - $start) * 1000;
        
        $this->line("  - Cache write: {$writeTime}ms");
        $this->line("  - Cache read: {$readTime}ms");
        
        if ($readTime > 10) {
            $this->warn("  ⚠️  Cache read time exceeds 10ms");
        } else {
            $this->info("  ✅ Cache performance OK");
        }
    }

    private function checkMemoryUsage()
    {
        $memory = memory_get_usage(true) / 1024 / 1024;
        $peak = memory_get_peak_usage(true) / 1024 / 1024;
        
        $this->info('💾 Memory Usage:');
        $this->line("  - Current: {$memory}MB");
        $this->line("  - Peak: {$peak}MB");
        
        if ($memory > 128) {
            $this->warn("  ⚠️  Memory usage exceeds 128MB");
        } else {
            $this->info("  ✅ Memory usage OK");
        }
    }

    private function checkResponseTime()
    {
        $this->info('⚡ API Response Time:');
        
        $start = microtime(true);
        // Simulate API call
        sleep(0.05); // 50ms simulation
        $responseTime = (microtime(true) - $start) * 1000;
        
        $this->line("  - Simulated response: {$responseTime}ms");
        
        if ($responseTime > 200) {
            $this->warn("  ⚠️  Response time exceeds 200ms target");
        } else {
            $this->info("  ✅ Response time within target");
        }
    }
}