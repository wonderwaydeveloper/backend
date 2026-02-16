<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UnifiedSecurityMiddleware
{
    public function __construct(
        private SecurityMonitoringService $security,
        private \App\Services\RateLimitingService $rateLimiter
    ) {}
    
    public function handle(Request $request, Closure $next, string $type = 'general')
    {
        // Skip in testing
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $ip = $request->ip();
        
        // Check IP whitelist
        if (in_array($ip, config('authentication.waf.admin_allowed_ips', []))) {
            return $next($request);
        }
        
        // Check if IP is blocked
        if ($this->security->isIPBlocked($ip)) {
            // Log the blocked access attempt
            app(\App\Services\AuditTrailService::class)->logSecurityEvent('blocked_ip_access', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent()
            ], $request);
            
            return response()->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        // Rate limiting check with centralized service
        // Use the type directly without 'api.' prefix
        $rateCheck = $this->rateLimiter->checkLimit($type, $ip);
        
        if (!$rateCheck['allowed']) {
            app(\App\Services\AuditTrailService::class)->logSecurityEvent('rate_limit_exceeded', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'type' => $type,
                'attempts' => $rateCheck['attempts'] ?? 0
            ], $request);
            
            return response()->json([
                'error' => $rateCheck['error'],
                'retry_after' => $rateCheck['retry_after'] ?? config('security.rate_limiting.default_retry_after')
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        // Threat detection
        $threatAnalysis = $this->security->calculateThreatScore($request);
        
        if ($threatAnalysis['action'] === 'block') {
            // Log threat detection before blocking
            app(\App\Services\AuditTrailService::class)->logSecurityEvent('threat_detected', [
                'threat_score' => $threatAnalysis['score'],
                'reasons' => $threatAnalysis['reasons'],
                'ip' => $ip,
                'endpoint' => $request->path()
            ], $request);
            
            $this->security->blockIP($ip);
            
            // Log IP blocking
            app(\App\Services\AuditTrailService::class)->logSecurityEvent('ip_blocked', [
                'ip' => $ip,
                'duration' => config('security.threat_detection.ip_block_duration'),
                'reason' => 'high_threat_score'
            ], $request);
            
            return response()->json(['error' => 'Security threat detected'], Response::HTTP_FORBIDDEN);
        }
        
        $response = $next($request);
        
        // Add security headers
        return $response->withHeaders([
            'X-RateLimit-Remaining' => $rateCheck['remaining'] ?? 0,
            'X-RateLimit-Limit' => $this->rateLimiter->getConfig($type)['max_attempts'] ?? config('security.rate_limiting.default_window'),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block'
        ]);
    }
}