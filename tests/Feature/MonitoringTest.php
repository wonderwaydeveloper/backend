<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AdvancedMonitoringService;
use App\Services\ErrorTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_monitoring_dashboard()
    {
        $user = User::factory()->create();

        $this->mock(AdvancedMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getSystemMetrics')
                ->andReturn(['cpu' => 50, 'memory' => 60]);
        });

        $this->mock(ErrorTrackingService::class, function ($mock) {
            $mock->shouldReceive('getTopErrors')
                ->andReturn(['error1', 'error2']);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'system_metrics',
                'error_stats',
                'status'
            ]);
    }

    public function test_can_get_system_metrics()
    {
        $user = User::factory()->create();

        $this->mock(AdvancedMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getSystemMetrics')
                ->andReturn(['cpu' => 50, 'memory' => 60]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/metrics');

        $response->assertStatus(200)
            ->assertJson(['cpu' => 50, 'memory' => 60]);
    }

    public function test_can_get_error_stats()
    {
        $user = User::factory()->create();

        $this->mock(ErrorTrackingService::class, function ($mock) {
            $mock->shouldReceive('getTopErrors')
                ->andReturn(['error1', 'error2']);
            $mock->shouldReceive('getErrorStats')
                ->andReturn(['total' => 10, 'today' => 2]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/errors');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'top_errors',
                'error_stats'
            ]);
    }

    public function test_can_get_performance_metrics()
    {
        $user = User::factory()->create();

        $this->mock(AdvancedMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getDatabaseMetrics')
                ->andReturn(['connections' => 5]);
            $mock->shouldReceive('getCacheMetrics')
                ->andReturn(['hit_rate' => 95]);
            $mock->shouldReceive('getMemoryMetrics')
                ->andReturn(['usage' => 70]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'database',
                'cache',
                'memory'
            ]);
    }

    public function test_can_get_cache_metrics()
    {
        $user = User::factory()->create();

        $this->mock(AdvancedMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getCacheMetrics')
                ->andReturn(['hit_rate' => 95]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/cache');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'cluster_info',
                'node_health',
                'cache_stats'
            ]);
    }

    public function test_can_get_queue_metrics()
    {
        $user = User::factory()->create();

        $this->mock(AdvancedMonitoringService::class, function ($mock) {
            $mock->shouldReceive('getQueueMetrics')
                ->andReturn(['pending' => 5, 'processed' => 100]);
        });

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/monitoring/queue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats',
                'failed_jobs'
            ]);
    }

    public function test_guest_cannot_access_monitoring()
    {
        $response = $this->getJson('/api/monitoring/dashboard');

        $response->assertStatus(401);
    }
}