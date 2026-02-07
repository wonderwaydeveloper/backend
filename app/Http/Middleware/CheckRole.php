<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!auth()->user()->hasAnyRole($roles)) {
            return response()->json([
                'message' => 'You do not have the required role to access this resource',
                'required_roles' => $roles
            ], 403);
        }

        return $next($request);
    }
}
