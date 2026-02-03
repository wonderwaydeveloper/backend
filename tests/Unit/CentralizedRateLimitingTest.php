<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\SecurityMonitoringService;
use Illuminate\Support\Facades\Cache;

class CentralizedRateLimitingTest extends TestCase
{
    private SecurityMonitoringService $securityService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->securityService = new SecurityMonitoringService();
        Cache::flush(); // Clean cache for testing
    }
    
    public function test_rate_limiting_allows_requests_within_limit()
    {
        $result = $this->securityService->checkRateLimit('test_key', 5, 1);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['attempts']);
        $this->assertEquals(4, $result['remaining']);
    }
    
    public function test_rate_limiting_blocks_requests_over_limit()
    {
        // Make 5 requests (at limit)
        for ($i = 0; $i < 5; $i++) {
            $this->securityService->checkRateLimit('test_key', 5, 1);
        }
        
        // 6th request should be blocked
        $result = $this->securityService->checkRateLimit('test_key', 5, 1);
        
        $this->assertFalse($result['allowed']);
        $this->assertEquals('Rate limit exceeded', $result['error']);
        $this->assertArrayHasKey('retry_after', $result);
    }
    
    public function test_different_keys_have_separate_limits()
    {
        // Fill up first key
        for ($i = 0; $i < 5; $i++) {
            $this->securityService->checkRateLimit('key1', 5, 1);
        }
        
        // Second key should still work
        $result = $this->securityService->checkRateLimit('key2', 5, 1);
        
        $this->assertTrue($result['allowed']);
        $this->assertEquals(1, $result['attempts']);
    }
}