<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserModeration
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if user is banned
        if ($user->is_banned) {
            auth()->logout();
            return response()->json([
                'message' => 'Your account has been permanently banned.',
                'reason' => 'Terms of Service violation',
                'banned_at' => $user->banned_at,
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if user is suspended
        if ($user->is_suspended) {
            $suspendedUntil = $user->suspended_until;
            
            // Check if suspension has expired
            if ($suspendedUntil && now()->greaterThan($suspendedUntil)) {
                $user->update([
                    'is_suspended' => false,
                    'suspended_until' => null,
                ]);
                return $next($request);
            }

            auth()->logout();
            return response()->json([
                'message' => 'Your account has been temporarily suspended.',
                'suspended_until' => $suspendedUntil,
                'reason' => 'Community Guidelines violation',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
