<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Verify2FA
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->two_factor_enabled) {
            $sessionKey = '2fa_verified_' . $user->id;
            $verified = $request->session()->get($sessionKey);
            
            if (!$verified || $verified !== hash('sha256', $user->id . $user->updated_at)) {
                return response()->json([
                    'message' => '2FA verification required',
                    'requires_2fa' => true,
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
