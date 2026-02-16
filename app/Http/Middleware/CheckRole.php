<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (!auth()->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'You do not have the required role to access this resource',
                'required_roles' => $roles
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
