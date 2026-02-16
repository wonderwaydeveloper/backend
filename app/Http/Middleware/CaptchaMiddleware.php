<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CaptchaMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in testing and development environments
        if (app()->environment(['testing', 'local'])) {
            return $next($request);
        }
        
        $identifier = $this->getIdentifier($request);
        $failedAttempts = Cache::get("failed_login:{$identifier}", 0);
        
        // Require CAPTCHA after failed attempts threshold
        if ($failedAttempts >= config('security.captcha.failed_attempts_threshold')) {
            $captchaToken = $request->input('captcha_token');
            
            if (!$captchaToken) {
                return response()->json([
                    'error' => 'CAPTCHA verification required',
                    'requires_captcha' => true,
                    'failed_attempts' => $failedAttempts
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }
            
            if (!$this->verifyCaptcha($captchaToken, $request->ip())) {
                return response()->json([
                    'error' => 'Invalid CAPTCHA',
                    'requires_captcha' => true
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            // CAPTCHA verified - reset counter
            Cache::forget("failed_login:{$identifier}");
        }
        
        return $next($request);
    }
    
    private function getIdentifier(Request $request): string
    {
        // Use login field if present, otherwise IP
        return $request->input('login') ?? $request->ip();
    }
    
    private function verifyCaptcha(string $token, string $ip): bool
    {
        $recaptchaSecret = config('services.recaptcha.secret_key');
        
        // If no reCAPTCHA configured, skip verification (development mode)
        if (!$recaptchaSecret) {
            \Log::warning('reCAPTCHA not configured - skipping verification');
            return true;
        }
        
        try {
            $response = \Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $recaptchaSecret,
                'response' => $token,
                'remoteip' => $ip
            ]);
            
            $result = $response->json();
            
            return ($result['success'] ?? false) && ($result['score'] ?? 0) >= config('security.captcha.min_score');
        } catch (\Exception $e) {
            \Log::error('CAPTCHA verification failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
