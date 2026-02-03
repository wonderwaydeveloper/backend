<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitoringService;
use Closure;
use Illuminate\Http\Request;

class UnifiedSecurityMiddleware
{
    private SecurityMonitoringService $security;
    
    public function __construct(SecurityMonitoringService $security)
    {
        $this->security = $security;
    }
    
    public function handle(Request $request, Closure $next, string $type = 'general')
    {
        // Skip in testing
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $ip = $request->ip();
        
        // Check if IP is blocked
        if ($this->security->isIPBlocked($ip)) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        // Rate limiting check
        $rateCheck = $this->security->checkRateLimit("middleware:{$type}:{$ip}", 60, 1);
        if (!$rateCheck['allowed']) {
            return response()->json([
                'error' => $rateCheck['error'],
                'retry_after' => $rateCheck['retry_after']
            ], 429);
        }
        
        // Threat detection
        $threatAnalysis = $this->security->calculateThreatScore($request);
        
        if ($threatAnalysis['action'] === 'block') {
            $this->security->blockIP($ip, 3600, 'high_threat_score');
            return response()->json(['error' => 'Security threat detected'], 403);
        }
        
        $response = $next($request);
        
        // Add security headers
        return $response->withHeaders([
            'X-RateLimit-Remaining' => $rateCheck['remaining'],
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block'
        ]);
    }
}