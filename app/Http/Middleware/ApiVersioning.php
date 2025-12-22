<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiVersioning
{
    public function handle(Request $request, Closure $next, string $version = 'v1')
    {
        // Set API version in request
        $request->attributes->set('api_version', $version);
        
        // Add version to response headers
        $response = $next($request);
        
        return $response->header('API-Version', $version)
                       ->header('API-Supported-Versions', 'v1, v2');
    }
}