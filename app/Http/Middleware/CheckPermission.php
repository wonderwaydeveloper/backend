<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!auth()->user()->can($permission)) {
            return response()->json([
                'message' => 'You do not have permission to perform this action',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
