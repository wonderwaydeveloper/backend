<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyticsTrackRequest;
use App\Models\Post;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        $metrics = $this->analyticsService->getDashboardMetrics($request->user());

        return response()->json([
            'dashboard' => $metrics,
        ]);
    }

    public function userAnalytics(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,30d,90d',
        ]);

        $analytics = $this->analyticsService->getUserAnalytics(
            $request->user(),
            $request->get('period', '30d')
        );

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    public function postAnalytics(Request $request, Post $post): JsonResponse
    {
        // Only allow post owner to view analytics
        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $request->validate([
            'period' => 'nullable|in:7d,30d,90d',
        ]);

        $analytics = $this->analyticsService->getPostAnalytics(
            $post->id,
            $request->get('period', '7d')
        );

        return response()->json([
            'post_analytics' => $analytics,
        ]);
    }

    public function trackEvent(AnalyticsTrackRequest $request): JsonResponse
    {
        $validated = $request->validated();

        \App\Models\AnalyticsEvent::track(
            $validated['event_type'],
            $validated['entity_type'] ?? 'unknown',
            $validated['entity_id'] ?? 0,
            $validated['user_id'] ?? $request->user()?->id,
            $validated['properties'] ?? []
        );

        return response()->json(['message' => 'Event tracked successfully']);
    }
}