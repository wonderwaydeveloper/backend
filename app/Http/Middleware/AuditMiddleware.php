<?php

namespace App\Http\Middleware;

use App\Services\AuditTrailService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditMiddleware
{
    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Only log authenticated requests
        if (Auth::check()) {
            $this->logRequest($request, $response);
        }
        
        return $response;
    }

    private function logRequest(Request $request, $response): void
    {
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();
        
        // Skip logging for certain endpoints
        if ($this->shouldSkipLogging($path)) {
            return;
        }
        
        $action = $this->determineAction($method, $path);
        
        if ($action) {
            $this->auditService->log($action, [
                'method' => $method,
                'path' => $path,
                'status_code' => $statusCode,
                'response_time' => $this->getResponseTime($request),
                'payload_size' => strlen($request->getContent())
            ], $request);
        }
    }

    private function shouldSkipLogging(string $path): bool
    {
        $skipPaths = [
            'api/auth/me',
            'api/health',
            'api/notifications/unread-count'
        ];
        
        foreach ($skipPaths as $skipPath) {
            if (str_starts_with($path, $skipPath)) {
                return true;
            }
        }
        
        return false;
    }

    private function determineAction(string $method, string $path): ?string
    {
        // Map HTTP methods and paths to audit actions
        $patterns = [
            'POST /api/posts' => 'post.create',
            'PUT /api/posts/' => 'post.update',
            'DELETE /api/posts/' => 'post.delete',
            'POST /api/auth/login' => 'auth.login',
            'POST /api/auth/logout' => 'auth.logout',
            'POST /api/auth/password/change' => 'auth.password_change',
            'PUT /api/profile' => 'user.profile_update',
            'DELETE /api/users/' => 'user.delete',
        ];
        
        foreach ($patterns as $pattern => $action) {
            [$patternMethod, $patternPath] = explode(' ', $pattern, 2);
            
            if ($method === $patternMethod && 
                (str_starts_with($path, ltrim($patternPath, '/')) || $path === ltrim($patternPath, '/'))) {
                return $action;
            }
        }
        
        return null;
    }

    private function getResponseTime(Request $request): float
    {
        $startTime = $request->server('REQUEST_TIME_FLOAT');
        return $startTime ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    }
}