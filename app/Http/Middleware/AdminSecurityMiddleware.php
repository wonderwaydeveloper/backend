<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        // Skip security checks for tests
        if (app()->environment('testing')) {
            return $next($request);
        }
        
        // IP Whitelist check
        $allowedIPs = config('security.admin_allowed_ips', []);
        if (!empty($allowedIPs) && !in_array($request->ip(), $allowedIPs)) {
            Log::warning('Unauthorized admin access attempt', ['ip' => $request->ip()]);
            abort(403, 'Access denied');
        }

        // Two-factor authentication check
        if ($user && method_exists($user, 'hasVerifiedTwoFactor') && !$user->hasVerifiedTwoFactor()) {
            return redirect()->route('filament.admin.auth.two-factor');
        }

        // Log admin activities
        Log::info('Admin panel access', [
            'user_id' => $user?->id,
            'ip' => $request->ip(),
            'route' => $request->route()?->getName(),
            'user_agent' => $request->userAgent()
        ]);

        return $next($request);
    }
}