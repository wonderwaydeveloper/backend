<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleWithConfig
{
    public function handle(Request $request, Closure $next, string $configKey): Response
    {
        $config = $this->getThrottleConfig($configKey);
        
        if (!$config) {
            return $next($request);
        }
        
        $key = $this->resolveRequestSignature($request, $configKey);
        $maxAttempts = $config['max_attempts'];
        $decayMinutes = $config['window_minutes'];
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => RateLimiter::availableIn($key),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $response->withHeaders([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
        ]);
    }
    
    private function getThrottleConfig(string $configKey): ?array
    {
        // Parse config key like 'auth.login' or 'search.posts'
        $parts = explode('.', $configKey);
        
        if (count($parts) !== 2) {
            return null;
        }
        
        return config("security.rate_limiting.{$parts[0]}.{$parts[1]}");
    }
    
    private function resolveRequestSignature(Request $request, string $configKey): string
    {
        $user = $request->user();
        
        if ($user) {
            return "throttle:{$configKey}:{$user->id}";
        }
        
        return "throttle:{$configKey}:{$request->ip()}";
    }
}
