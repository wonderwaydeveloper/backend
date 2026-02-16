<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CSRFProtection
{
    private array $excludedRoutes = [
        'api/login',
        'api/register',
        'api/health',
        'api/test'
    ];
    
    public function handle(Request $request, Closure $next)
    {
        // Skip CSRF for API routes using Sanctum token authentication
        if ($request->is('api/*') && $request->bearerToken()) {
            return $next($request);
        }
        
        // Skip CSRF for excluded routes
        if ($this->shouldSkip($request)) {
            return $next($request);
        }
        
        // Only check for state-changing methods
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $this->validateCSRFToken($request);
        }
        
        return $next($request);
    }
    
    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();
        
        foreach ($this->excludedRoutes as $route) {
            if (Str::is($route, $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function validateCSRFToken(Request $request): void
    {
        $token = $this->getTokenFromRequest($request);
        
        if (!$token) {
            abort(config('security.http_status.csrf_token_mismatch'), 'CSRF token missing');
        }
        
        if (!$this->isValidToken($token, $request)) {
            abort(config('security.http_status.csrf_token_mismatch'), 'CSRF token mismatch');
        }
    }
    
    private function getTokenFromRequest(Request $request): ?string
    {
        return $request->header('X-CSRF-TOKEN') 
            ?? $request->input('_token')
            ?? $request->header('X-XSRF-TOKEN');
    }
    
    private function isValidToken(?string $token, Request $request): bool
    {
        if (!$token) {
            return false;
        }
        
        $sessionToken = $request->session()->token();
        
        return $sessionToken && hash_equals($sessionToken, $token);
    }
}