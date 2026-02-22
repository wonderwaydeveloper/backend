<?php

namespace App\Http\Middleware;

use App\Services\SecurityMonitoringService;
use App\Services\RateLimitingService;
use App\Services\AuditTrailService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
{
    public function __construct(
        private SecurityMonitoringService $security,
        private RateLimitingService $rateLimiter,
        private AuditTrailService $audit
    ) {}
    
    public function handle(Request $request, Closure $next, string $type = 'general')
    {
        // Skip in testing
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $ip = $request->ip();
        
        // Check IP whitelist
        if (in_array($ip, config('security.waf.admin_allowed_ips', []))) {
            return $next($request);
        }
        
        // Check if IP is blocked
        if ($this->security->isIPBlocked($ip)) {
            $this->audit->logSecurityEvent('blocked_ip_access', [
                'ip' => $ip,
                'endpoint' => $request->path(),
                'user_agent' => $request->userAgent()
            ], $request);
            
            return response()->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        
        // Rate limiting check
        $rateCheck = $this->rateLimiter->checkLimit($type, $ip);
        
        if (!$rateCheck['allowed']) {
            $this->audit->logSecurityEvent('rate_limit_exceeded', [
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
            $this->audit->logSecurityEvent('threat_detected', [
                'threat_score' => $threatAnalysis['score'],
                'reasons' => $threatAnalysis['reasons'],
                'ip' => $ip,
                'endpoint' => $request->path()
            ], $request);
            
            $this->security->blockIP($ip);
            
            $this->audit->logSecurityEvent('ip_blocked', [
                'ip' => $ip,
                'duration' => config('security.threat_detection.ip_block_duration'),
                'reason' => 'high_threat_score'
            ], $request);
            
            return response()->json(['error' => 'Security threat detected'], Response::HTTP_FORBIDDEN);
        }
        
        $response = $next($request);
        
        // Skip security headers for admin panel
        if ($request->is('admin*')) {
            return $this->addRateLimitHeaders($response, $rateCheck, $type);
        }
        
        // Add all security headers
        return $this->addSecurityHeaders($response, $rateCheck, $type);
    }
    
    private function addSecurityHeaders($response, array $rateCheck, string $type)
    {
        $config = config('security.waf.headers', []);
        
        // Rate limit headers
        $response->headers->set('X-RateLimit-Remaining', $rateCheck['remaining'] ?? 0);
        $response->headers->set('X-RateLimit-Limit', $this->rateLimiter->getConfig($type)['max_attempts'] ?? config('security.rate_limiting.default_window'));
        
        if (empty($config) || !($config['enabled'] ?? false)) {
            return $response;
        }
        
        // HSTS Header
        if (isset($config['hsts']) && ($config['hsts']['enabled'] ?? false)) {
            $hsts = "max-age={$config['hsts']['max_age']}";
            if ($config['hsts']['include_subdomains'] ?? false) {
                $hsts .= '; includeSubDomains';
            }
            if ($config['hsts']['preload'] ?? false) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }
        
        // Content Security Policy
        if (isset($config['csp']) && ($config['csp']['enabled'] ?? false)) {
            $response->headers->set('Content-Security-Policy', $config['csp']['policy']);
        }
        
        // X-Frame-Options
        if (isset($config['x_frame_options'])) {
            $response->headers->set('X-Frame-Options', $config['x_frame_options']);
        }
        
        // X-Content-Type-Options
        if (isset($config['x_content_type_options'])) {
            $response->headers->set('X-Content-Type-Options', $config['x_content_type_options']);
        }
        
        // X-XSS-Protection
        if (isset($config['x_xss_protection'])) {
            $response->headers->set('X-XSS-Protection', $config['x_xss_protection']);
        }
        
        // Referrer Policy
        if (isset($config['referrer_policy'])) {
            $response->headers->set('Referrer-Policy', $config['referrer_policy']);
        }
        
        // Additional security headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        
        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
        
        return $response;
    }
    
    private function addRateLimitHeaders($response, array $rateCheck, string $type)
    {
        $response->headers->set('X-RateLimit-Remaining', $rateCheck['remaining'] ?? 0);
        $response->headers->set('X-RateLimit-Limit', $this->rateLimiter->getConfig($type)['max_attempts'] ?? config('security.rate_limiting.default_window'));
        
        return $response;
    }
}
