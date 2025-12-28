<?php

namespace App\Http\Middleware;

use App\Services\AdvancedRateLimiter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RateLimitMiddleware
{
    private AdvancedRateLimiter $rateLimiter;
    
    public function __construct(AdvancedRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }
    
    public function handle(Request $request, Closure $next, string $type = 'general')
    {
        // Skip rate limiting in testing environment
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        $ip = $this->getClientIp($request);
        
        // Check if IP is blocked
        if ($this->rateLimiter->isIpBlocked($ip)) {
            return $this->rateLimitResponse('IP temporarily blocked');
        }
        
        // Apply different rate limiting based on type
        switch ($type) {
            case 'login':
                return $this->handleLoginRateLimit($request, $next, $ip);
            case 'api':
                return $this->handleApiRateLimit($request, $next, $ip);
            case 'upload':
                return $this->handleUploadRateLimit($request, $next, $ip);
            default:
                return $this->handleGeneralRateLimit($request, $next, $ip);
        }
    }
    
    private function handleLoginRateLimit(Request $request, Closure $next, string $ip)
    {
        $identifier = $request->input('email', $ip);
        
        if (!$this->rateLimiter->checkLoginAttempts($identifier)) {
            return $this->rateLimitResponse('Too many login attempts. Please try again later.');
        }
        
        $response = $next($request);
        
        // Reset attempts on successful login
        if ($response->getStatusCode() === 200) {
            $this->rateLimiter->resetLoginAttempts($identifier);
        }
        
        return $response;
    }
    
    private function handleApiRateLimit(Request $request, Closure $next, string $ip)
    {
        // Check burst limit (10 requests in 10 seconds)
        if (!$this->rateLimiter->checkBurstLimit($ip)) {
            $this->rateLimiter->blockIpTemporarily($ip, 300); // 5 minutes
            return $this->rateLimitResponse('Rate limit exceeded. IP blocked temporarily.');
        }
        
        // Check per-minute limit
        $key = "api_rate:{$ip}";
        if (!$this->rateLimiter->attempt($key, 60, 1)) {
            return $this->rateLimitResponse('API rate limit exceeded', $key, 60);
        }
        
        // Check hourly limit
        if (!$this->rateLimiter->checkHourlyLimit($ip)) {
            $this->rateLimiter->blockIpTemporarily($ip, 3600); // 1 hour
            return $this->rateLimitResponse('Hourly limit exceeded. IP blocked.');
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $key, 60);
        
        return $response;
    }
    
    private function handleUploadRateLimit(Request $request, Closure $next, string $ip)
    {
        $key = "upload_rate:{$ip}";
        
        // Stricter limits for uploads (10 per minute)
        if (!$this->rateLimiter->attempt($key, 10, 1)) {
            return $this->rateLimitResponse('Upload rate limit exceeded', $key, 10);
        }
        
        $response = $next($request);
        $this->addRateLimitHeaders($response, $key, 10);
        
        return $response;
    }
    
    private function handleGeneralRateLimit(Request $request, Closure $next, string $ip)
    {
        $key = "general_rate:{$ip}";
        
        // General rate limit (200 per minute for testing)
        if (!$this->rateLimiter->attempt($key, 200, 1)) {
            return $this->rateLimitResponse('Rate limit exceeded', $key, 200);
        }
        
        $response = $next($request);
        $this->addRateLimitHeaders($response, $key, 200);
        
        return $response;
    }
    
    private function rateLimitResponse(string $message, string $key = null, int $maxAttempts = null)
    {
        $data = ['error' => $message];
        
        if ($key && $maxAttempts) {
            $data['retry_after'] = $this->rateLimiter->getRetryAfter($key);
            $data['remaining'] = $this->rateLimiter->getRemainingAttempts($key, $maxAttempts);
        }
        
        return response()->json($data, 429);
    }
    
    private function addRateLimitHeaders($response, string $key, int $maxAttempts): void
    {
        $remaining = $this->rateLimiter->getRemainingAttempts($key, $maxAttempts);
        $retryAfter = $this->rateLimiter->getRetryAfter($key);
        
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);
        
        if ($remaining === 0) {
            $response->headers->set('Retry-After', $retryAfter);
        }
    }
    
    private function getClientIp(Request $request): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            $ip = $request->server($header);
            if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
        
        return $request->ip();
    }
}