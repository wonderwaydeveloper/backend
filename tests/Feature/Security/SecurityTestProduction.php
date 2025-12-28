<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use App\Services\AdvancedRateLimiter;

class SecurityTestProduction extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall();
        // Force production environment for security tests
        app()->detectEnvironment(function () {
            return 'production';
        });
    }
    
    /** @test */
    public function waf_blocks_sql_injection_attempts()
    {
        $maliciousPayloads = [
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users--",
            "admin'--",
            "' OR 1=1#"
        ];
        
        foreach ($maliciousPayloads as $payload) {
            $response = $this->postJson('/api/test', [
                'content' => $payload
            ]);
            
            $response->assertStatus(403);
        }
    }
    
    /** @test */
    public function waf_blocks_xss_attempts()
    {
        $maliciousPayloads = [
            '<script>alert("xss")</script>',
            '<iframe src="javascript:alert(1)"></iframe>',
            '<img src=x onerror=alert(1)>',
            'javascript:alert(1)',
            '<svg onload=alert(1)>',
            '<body onload=alert(1)>'
        ];
        
        foreach ($maliciousPayloads as $payload) {
            $response = $this->postJson('/api/test', [
                'content' => $payload
            ]);
            
            $response->assertStatus(403);
        }
    }
    
    /** @test */
    public function suspicious_user_agents_are_detected()
    {
        $suspiciousAgents = [
            'sqlmap/1.0',
            'Nikto/2.1.6',
            'w3af.org',
            'Burp Suite'
        ];
        
        foreach ($suspiciousAgents as $agent) {
            $response = $this->withHeaders([
                'User-Agent' => $agent
            ])->getJson('/api/health');
            
            $response->assertStatus(403);
        }
    }
    
    /** @test */
    public function ip_blocking_works()
    {
        $rateLimiter = app(AdvancedRateLimiter::class);
        $testIp = '192.168.1.100';
        
        // Block IP
        $rateLimiter->blockIpTemporarily($testIp, 60);
        
        // Simulate request from blocked IP
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => $testIp
        ])->getJson('/api/health');
        
        $response->assertStatus(403);
    }
}