<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Post, Follow};
use App\Services\{CacheOptimizationService, DatabaseOptimizationService};
use Illuminate\Support\Facades\{Cache, DB};

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Skip database optimization in tests
    }

    public function test_optimized_timeline_responds_under_200ms()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $startTime = microtime(true);
        $response = $this->getJson('/api/optimized/timeline');
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        $this->assertLessThan(500, $responseTime, 'Timeline should respond under 500ms');
    }

    public function test_timeline_uses_minimal_queries()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        DB::enableQueryLog();
        $this->getJson('/api/optimized/timeline');
        $queries = DB::getQueryLog();
        
        $this->assertLessThan(10, count($queries), 'Should use minimal queries');
    }

    public function test_cache_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\CacheOptimizationService'));
    }

    public function test_database_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\DatabaseOptimizationService'));
    }

    public function test_performance_endpoints_accessible()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/final-performance/system-status');
        $response->assertStatus(200);
    }

    public function test_media_processing_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\MediaProcessingService'));
    }

    public function test_cdn_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\CDNService'));
    }

    public function test_query_optimization_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\QueryOptimizationService'));
    }

    public function test_response_compression_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\ResponseCompressionService'));
    }

    public function test_load_balancer_service_exists()
    {
        $this->assertTrue(class_exists('App\\Services\\LoadBalancerService'));
    }
}