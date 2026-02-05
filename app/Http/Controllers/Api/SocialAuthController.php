<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\{DeviceFingerprintService, EmailService};
use App\Models\{User, DeviceToken};
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\{Cache, Hash};
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function __construct(
        private DeviceFingerprintService $deviceService,
        private EmailService $emailService,
        private \App\Services\RateLimitingService $rateLimiter,
        private \App\Services\SessionTimeoutService $timeoutService
    ) {}

    public function redirect($provider, Request $request)
    {
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function callback(Request $request, $provider)
    {
        $rateLimitResult = $this->rateLimiter->checkLimit('auth.social', $request->ip());
        
        if (!$rateLimitResult['allowed']) {
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            $queryParams = http_build_query([
                'error' => 'rate_limit_exceeded',
                'retry_after' => $rateLimitResult['retry_after']
            ]);
            return redirect($frontendUrl . '/social/callback?' . $queryParams);
        }
        
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            
            $user = User::where('email', $socialUser->getEmail())->first();
            $isNewUser = false;
            
            if (!$user) {
                // Twitter-style: Always auto-register new users
                $isNewUser = true;
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'username' => $this->generateUsername($socialUser->getName()),
                    'password' => Hash::make(Str::random(32)),
                    'password_changed_at' => now(),
                    'email_verified_at' => now(),
                    'avatar' => $socialUser->getAvatar(),
                    'google_id' => $socialUser->getId(),
                ]);
                
                try {
                    $user->assignRole('user');
                } catch (\Exception $e) {
                    // Continue without role
                }
            } else {
                // User exists - update social ID
                $user->update(['google_id' => $socialUser->getId()]);
            }

            $fingerprint = $this->deviceService->generate($request);
            
            // Skip device verification for new users
            if ($isNewUser) {
                // Create trusted device for new user
                $user->devices()->create([
                    'token' => 'device_' . Str::random(40),
                    'fingerprint' => $fingerprint,
                    'device_name' => $this->getDeviceNameFromUserAgent($request->userAgent()),
                    'device_type' => $this->getDeviceTypeFromUserAgent($request->userAgent()),
                    'browser' => $this->getBrowserFromUserAgent($request->userAgent()),
                    'os' => $this->getOSFromUserAgent($request->userAgent()),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'is_trusted' => true,
                    'last_used_at' => now()
                ]);
                
                $token = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
                
                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                
                $queryParams = http_build_query([
                    'token' => $token,
                    'requires_age_verification' => !$user->date_of_birth,
                    'provider' => $provider
                ]);
                
                return redirect($frontendUrl . '/social/callback?' . $queryParams);
            }
            
            $trustedDevice = $user->devices()->where('fingerprint', $fingerprint)->where('is_trusted', true)->first();

            if ($trustedDevice) {
                $token = $this->timeoutService->createTokenWithExpiry($user, 'auth_token')->plainTextToken;
                
                $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
                
                $queryParams = http_build_query([
                    'token' => $token,
                    'requires_age_verification' => !$user->date_of_birth,
                    'provider' => $provider
                ]);
                
                return redirect($frontendUrl . '/social/callback?' . $queryParams);
            }

            $code = random_int(100000, 999999);
            
            $verificationData = [
                'code' => $code,
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'device_info' => [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'location' => 'Unknown Location'
                ],
                'code_sent_at' => now()->timestamp,
                'expires_at' => now()->addMinutes($this->timeoutService->getDeviceVerificationExpiry())->timestamp,
                'resend_count' => 0
            ];
            
            Cache::put("device_verification_by_fingerprint:{$fingerprint}", $verificationData, now()->addMinutes($this->timeoutService->getDeviceVerificationExpiry()));
            
            $this->emailService->sendDeviceVerificationEmail($user, $code, $verificationData['device_info']);
            
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            
            $queryParams = http_build_query([
                'requires_device_verification' => 'true',
                'user_id' => $user->id,
                'fingerprint' => $fingerprint,
                'requires_age_verification' => !$user->date_of_birth ? 'true' : 'false',
                'provider' => $provider,
                'message' => 'Device verification required. Check your email for verification code.',
                'code_expires_at' => $verificationData['expires_at'],
                'resend_available_at' => now()->addSeconds(30)->timestamp
            ]);
            
            return redirect($frontendUrl . '/social/callback?' . $queryParams);

        } catch (\Exception $e) {
            \Log::error('Social auth error: ' . $e->getMessage(), [
                'provider' => $provider,
                'error_type' => get_class($e)
            ]);
            
            $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
            return redirect($frontendUrl . '/social/callback?error=social_auth_failed');
        }
    }
    
    private function generateUsername($name): string
    {
        $username = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($name));
        
        if (empty($username) || is_numeric($username[0])) {
            $username = 'user' . $username;
        }
        
        $username = substr($username, 0, 15);
        
        if (strlen($username) < 4) {
            $username = $username . str_repeat('x', 4 - strlen($username));
        }
        
        $originalUsername = $username;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $suffix = (string)$count;
            $maxBase = 15 - strlen($suffix);
            $username = substr($originalUsername, 0, $maxBase) . $suffix;
            $count++;
        }
        
        return $username;
    }
    
    private function getDeviceNameFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'Mobile Device';
        if (str_contains($userAgent, 'Tablet')) return 'Tablet';
        return 'Desktop';
    }
    
    private function getDeviceTypeFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'android';
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) return 'ios';
        return 'web';
    }
    
    private function getBrowserFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        return 'Unknown';
    }
    
    private function getOSFromUserAgent(string $userAgent): string
    {
        if (str_contains($userAgent, 'Windows')) return 'Windows';
        if (str_contains($userAgent, 'Mac')) return 'macOS';
        if (str_contains($userAgent, 'Linux')) return 'Linux';
        if (str_contains($userAgent, 'Android')) return 'Android';
        if (str_contains($userAgent, 'iOS')) return 'iOS';
        return 'Unknown';
    }
}