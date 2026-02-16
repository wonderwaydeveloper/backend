<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $requiredRole  Required role: premium, organization
     */
    public function handle(Request $request, Closure $next, string $requiredRole = 'premium'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->hasRole($requiredRole)) {
            $limitService = app(\App\Services\SubscriptionLimitService::class);
            return response()->json([
                'message' => "This feature requires {$requiredRole} subscription",
                'current_role' => $limitService->getUserHighestRole($user),
                'required_role' => $requiredRole,
                'upgrade_url' => '/monetization/premium/plans',
            ], Response::HTTP_FORBIDDEN);
        }

        // Check if subscription is active (for premium/organization)
        if (in_array($requiredRole, ['premium', 'organization'])) {
            if ($user->is_premium && !$user->activePremiumSubscription()->exists()) {
                return response()->json([
                    'message' => 'Your subscription has expired',
                    'renew_url' => '/monetization/premium/plans',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        return $next($request);
    }
}
