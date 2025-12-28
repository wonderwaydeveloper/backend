<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        $config = config('security.headers');
        
        if (!$config['enabled']) {
            return $response;
        }
        
        // HSTS Header
        if ($config['hsts']['enabled']) {
            $hsts = "max-age={$config['hsts']['max_age']}";
            if ($config['hsts']['include_subdomains']) {
                $hsts .= '; includeSubDomains';
            }
            if ($config['hsts']['preload']) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }
        
        // Content Security Policy
        if ($config['csp']['enabled']) {
            $response->headers->set('Content-Security-Policy', $config['csp']['policy']);
        }
        
        // X-Frame-Options
        $response->headers->set('X-Frame-Options', $config['x_frame_options']);
        
        // X-Content-Type-Options
        $response->headers->set('X-Content-Type-Options', $config['x_content_type_options']);
        
        // X-XSS-Protection
        $response->headers->set('X-XSS-Protection', $config['x_xss_protection']);
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', $config['referrer_policy']);
        
        // Additional security headers
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-Download-Options', 'noopen');
        $response->headers->set('X-DNS-Prefetch-Control', 'off');
        
        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
        
        return $response;
    }
}