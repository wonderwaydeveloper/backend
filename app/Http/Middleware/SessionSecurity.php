<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class SessionSecurity
{
    public function handle(Request $request, Closure $next)
    {
        // Skip session security for admin panel to avoid CSRF issues
        if ($request->is('admin*')) {
            return $next($request);
        }
        
        // Skip if Redis is not available
        try {
            Redis::ping();
        } catch (\Exception $e) {
            return $next($request);
        }
        
        // Check session security
        if ($request->hasSession()) {
            $this->validateSession($request);
            $this->updateSessionActivity($request);
        }
        
        $response = $next($request);
        
        // Set secure session cookies
        $this->setSecureCookies($response);
        
        return $response;
    }
    
    private function validateSession(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userId = auth()->id();
        
        if ($userId) {
            // Check for session hijacking
            $storedFingerprint = Redis::get("session_fp:{$sessionId}");
            $currentFingerprint = $this->generateFingerprint($request);
            
            if ($storedFingerprint && $storedFingerprint !== $currentFingerprint) {
                Log::warning('Possible session hijacking detected', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                // Invalidate session
                $request->session()->invalidate();
                auth()->logout();
                abort(401, 'Session security violation');
            }
            
            // Store/update fingerprint
            Redis::setex("session_fp:{$sessionId}", 7200, $currentFingerprint);
        }
    }
    
    private function updateSessionActivity(Request $request): void
    {
        $sessionId = $request->session()->getId();
        $userId = auth()->id();
        
        if ($userId) {
            $activityData = [
                'last_activity' => time(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path()
            ];
            
            Redis::setex("session_activity:{$sessionId}", 7200, json_encode($activityData));
        }
    }
    
    private function generateFingerprint(Request $request): string
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->ip()
        ];
        
        return hash('sha256', implode('|', $components));
    }
    
    private function setSecureCookies($response): void
    {
        $response->headers->set('Set-Cookie', 
            session()->getName() . '=' . session()->getId() . 
            '; HttpOnly; Secure; SameSite=Strict; Path=/'
        );
    }
}