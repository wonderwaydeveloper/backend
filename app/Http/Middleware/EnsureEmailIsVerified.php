<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email is not verified',
                'error' => 'EMAIL_NOT_VERIFIED',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
