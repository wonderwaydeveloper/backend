<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (!auth()->user()->can($permission)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action',
                'required_permission' => $permission
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
