<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        // Skip security headers for admin panel to avoid conflicts
        if ($request->is('admin*')) {
            return $response;
        }
        
        $config = config('security.headers', []);
        
        if (empty($config) || !($config['enabled'] ?? false)) {
            return $response;
        }
        
        // Only apply headers if config exists and is properly structured
        if (!is_array($config)) {
            return $response;
        }
        
        // HSTS Header
        if (isset($config['hsts']) && ($config['hsts']['enabled'] ?? false)) {
            $hsts = "max-age={$config['hsts']['max_age']}";
            if ($config['hsts']['include_subdomains'] ?? false) {
                $hsts .= '; includeSubDomains';
            }
            if ($config['hsts']['preload'] ?? false) {
                $hsts .= '; preload';
            }
            $response->headers->set('Strict-Transport-Security', $hsts);
        }
        
        // Content Security Policy
        if (isset($config['csp']) && ($config['csp']['enabled'] ?? false)) {
            $response->headers->set('Content-Security-Policy', $config['csp']['policy']);
        }
        
        // X-Frame-Options
        if (isset($config['x_frame_options'])) {
            $response->headers->set('X-Frame-Options', $config['x_frame_options']);
        }
        
        // X-Content-Type-Options
        if (isset($config['x_content_type_options'])) {
            $response->headers->set('X-Content-Type-Options', $config['x_content_type_options']);
        }
        
        // X-XSS-Protection
        if (isset($config['x_xss_protection'])) {
            $response->headers->set('X-XSS-Protection', $config['x_xss_protection']);
        }
        
        // Referrer Policy
        if (isset($config['referrer_policy'])) {
            $response->headers->set('Referrer-Policy', $config['referrer_policy']);
        }
        
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