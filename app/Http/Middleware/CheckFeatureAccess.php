<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    public function __construct(
        private SubscriptionLimitService $limitService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  string  $feature  Feature name: hd_upload, scheduled_posts, advertisements
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $hasAccess = match($feature) {
            'hd_upload' => $this->limitService->canUploadHD($user),
            'advertisements' => $this->limitService->canCreateAdvertisements($user),
            'scheduled_posts' => $this->limitService->getScheduledPostsLimit($user) > 0,
            default => false,
        };

        if (!$hasAccess) {
            return response()->json([
                'message' => 'This feature requires a premium subscription',
                'feature' => $feature,
                'upgrade_url' => '/monetization/premium/plans',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
