<?php

namespace App\Http\Middleware;

use App\Services\UnifiedSecurityService;
use Closure;
use Illuminate\Http\Request;

class UnifiedSecurityMiddleware
{
    private UnifiedSecurityService $security;
    
    public function __construct(UnifiedSecurityService $security)
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
        
        // Input validation and sanitization
        if ($this->security->detectAdvancedXss($request)) {
            return response()->json([
                'message' => 'Suspicious content detected',
                'error' => 'SUSPICIOUS_CONTENT',
            ], 400);
        }
        
        if ($this->security->detectAdvancedSqlInjection($request)) {
            return response()->json([
                'message' => 'Invalid request detected', 
                'error' => 'INVALID_REQUEST',
            ], 400);
        }
        
        // Sanitize inputs after detection
        $this->security->sanitizeInputs($request);
        
        // Device verification for authenticated users
        if ($request->user()) {
            $deviceCheck = $this->checkDeviceVerification($request);
            if ($deviceCheck) {
                return $deviceCheck;
            }
        }
        
        // Rate limiting check
        $rateCheck = $this->security->checkRateLimit($request, $type);
        if (!$rateCheck['allowed']) {
            $error = $rateCheck['error'] ?? 'Too many requests';
            return response()->json([
                'error' => $error,
                'retry_after' => $rateCheck['retry_after']
            ], $error === 'Account temporarily locked due to too many failed attempts' ? 423 : 429)
            ->header('Retry-After', $rateCheck['retry_after']);
        }
        
        // Threat detection
        $threatAnalysis = $this->security->calculateThreatScore($request);
        
        switch ($threatAnalysis['action']) {
            case 'block':
                $this->security->blockIP($ip, 3600, 'high_threat_score');
                return response()->json(['error' => 'Security threat detected'], 403);
                
            case 'challenge':
                return response()->json([
                    'error' => 'Security challenge required',
                    'challenge_type' => 'captcha'
                ], 429);
                
            case 'monitor':
                // Log but allow
                break;
        }
        
        $response = $next($request);
        
        // Handle login response for brute force protection
        $this->security->handleLoginResponse($response, $request);
        
        // Add security headers
        return $this->addSecurityHeaders($response, $rateCheck);
    }
    
    private function addSecurityHeaders($response, array $rateCheck)
    {
        return $response->withHeaders([
            'X-RateLimit-Remaining' => $rateCheck['remaining'],
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block'
        ]);
    }
    
    private function checkDeviceVerification(Request $request)
    {
        $fingerprint = $this->generateFingerprint($request);
        $deviceKey = "device:{$request->user()->id}:{$fingerprint}";
        
        // Check if device is known
        if (!\Illuminate\Support\Facades\Redis::exists($deviceKey)) {
            return response()->json([
                'error' => 'NEW_DEVICE_DETECTED',
                'message' => 'Please verify this new device',
                'requires_verification' => true
            ], 403);
        }
        
        // Check for suspicious activity
        if ($this->isDeviceSuspicious($request, $fingerprint)) {
            return response()->json([
                'error' => 'SUSPICIOUS_ACTIVITY',
                'message' => 'Additional verification required',
                'requires_verification' => true
            ], 403);
        }
        
        return null; // Device is OK
    }
    
    private function generateFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->userAgent(),
            $request->header('accept-language', ''),
            $request->header('accept-encoding', ''),
            $request->ip()
        ]));
    }
    
    private function isDeviceSuspicious(Request $request, string $fingerprint): bool
    {
        $key = "device_requests:{$fingerprint}";
        $requests = \Illuminate\Support\Facades\Redis::get($key, 0);
        
        return $requests > 100; // More than 100 requests per minute
    }
}