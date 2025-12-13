<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVerifiedEmail
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Authentication required',
                'error' => 'unauthenticated'
            ], 401);
        }
        
        // بررسی مسدود بودن حساب
        if ($user->is_banned) {
            return response()->json([
                'message' => 'Your account has been suspended.',
                'error' => 'account_banned',
                'support_contact' => 'support@wonderwaypictures.com'
            ], 403);
        }
        
        // بررسی تأیید حساب (ایمیل یا تلفن) و وضعیت active
        $verified = !is_null($user->email_verified_at) || !is_null($user->phone_verified_at);
        if (!$verified || $user->status !== 'active') {
            return response()->json([
                'message' => 'Account verification required to access this feature.',
                'error' => 'account_verification_required',
                'required_action' => 'verify_account',
                'user_id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'next_step' => $user->phone_verified_at ? [
                    'action' => 'verify_phone',
                    'endpoint' => '/api/auth/phone/login',
                    'description' => 'Please verify your phone number'
                ] : [
                    'action' => 'verify_email',
                    'endpoint' => '/api/auth/verify-and-login',
                    'description' => 'Please verify your email address'
                ]
            ], 403);
        }
        
        return $next($request);
    }
}