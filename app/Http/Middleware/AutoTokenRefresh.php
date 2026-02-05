<?php

namespace App\Http\Middleware;

use App\Services\TokenManagementService;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AutoTokenRefresh
{
    public function __construct(
        private TokenManagementService $tokenService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();
            
            if ($this->tokenService->shouldRefreshToken($token)) {
                try {
                    $newToken = $this->tokenService->refreshAccessToken($user, $token);
                    
                    // Add new token to response headers
                    $response = $next($request);
                    $response->headers->set('X-New-Token', $newToken);
                    $response->headers->set('X-Token-Refreshed', 'true');
                    
                    return $response;
                } catch (\Exception $e) {
                    // If refresh fails, continue with current token
                    \Log::warning('Token refresh failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $next($request);
    }
}