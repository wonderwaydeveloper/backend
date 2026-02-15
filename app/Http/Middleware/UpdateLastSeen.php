<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = "last_seen_updated:{$userId}";
            
            // Only update once per minute to reduce DB writes
            if (!Cache::has($cacheKey)) {
                Auth::user()->update([
                    'last_seen_at' => now(),
                ]);
                
                Cache::put($cacheKey, true, 60);
            }
        }

        return $next($request);
    }
}
