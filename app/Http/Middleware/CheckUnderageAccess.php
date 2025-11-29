<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUnderageAccess
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        // اگر کاربر زیر سن است و ویژگی مورد نظر محدود شده است
        if ($user && $user->is_underage) {
            $restrictedFeatures = [
                'private_messaging',
                'sensitive_content', 
                'video_upload',
                'live_streaming'
            ];

            if (in_array($feature, $restrictedFeatures)) {
                return response()->json([
                    'message' => 'این قابلیت برای کاربران زیر سن محدود شده است.',
                    'feature' => $feature,
                ], 403);
            }
        }

        return $next($request);
    }
}