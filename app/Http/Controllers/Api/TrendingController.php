<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrendingRequest;
use App\Http\Resources\TrendingResource;
use App\Services\TrendingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrendingController extends Controller
{
    private $trendingService;

    public function __construct(TrendingService $trendingService)
    {
        $this->trendingService = $trendingService;
    }

    public function hashtags(TrendingRequest $request)
    {
        $hashtags = $this->trendingService->getTrendingHashtags(
            $request->input('limit', config('limits.pagination.trending')),
            $request->input('timeframe', 24)
        );

        return response()->json([
            'data' => TrendingResource::collection($hashtags),
            'meta' => [
                'limit' => $request->input('limit', config('limits.pagination.trending')),
                'timeframe_hours' => $request->input('timeframe', 24),
                'generated_at' => now(),
            ],
        ]);
    }

    public function posts(TrendingRequest $request)
    {
        $posts = $this->trendingService->getTrendingPosts(
            $request->input('limit', config('limits.pagination.default')),
            $request->input('timeframe', 24)
        );

        return response()->json([
            'data' => TrendingResource::collection($posts),
            'meta' => [
                'limit' => $request->input('limit', config('limits.pagination.default')),
                'timeframe_hours' => $request->input('timeframe', 24),
                'generated_at' => now(),
            ],
        ]);
    }

    public function users(TrendingRequest $request)
    {
        $users = $this->trendingService->getTrendingUsers(
            $request->input('limit', config('limits.pagination.trending')),
            $request->input('timeframe', 168)
        );

        return response()->json([
            'data' => TrendingResource::collection($users),
            'meta' => [
                'limit' => $request->input('limit', config('limits.pagination.trending')),
                'timeframe_hours' => $request->input('timeframe', 168),
                'generated_at' => now(),
            ],
        ]);
    }

    public function personalized(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $content = $this->trendingService->getPersonalizedTrending(
            $request->user()->id,
            $request->input('limit', config('limits.pagination.trending'))
        );

        return response()->json([
            'data' => $content,
            'meta' => [
                'limit' => $request->input('limit', config('limits.pagination.trending')),
                'user_id' => $request->user()->id,
                'generated_at' => now(),
            ],
        ]);
    }

    public function velocity(Request $request, $type, $id)
    {
        $request->validate([
            'hours' => 'nullable|integer|min:1|max:24',
        ]);

        if (! in_array($type, ['hashtag', 'post'])) {
            return response()->json(['error' => 'Invalid type'], Response::HTTP_BAD_REQUEST);
        }

        $velocity = $this->trendingService->getTrendVelocity(
            $type,
            $id,
            $request->input('hours', 6)
        );

        return response()->json([
            'type' => $type,
            'id' => $id,
            'velocity' => $velocity,
            'hours_analyzed' => $request->input('hours', 6),
            'interpretation' => $velocity > 0 ? 'accelerating' : ($velocity < 0 ? 'decelerating' : 'stable'),
            'generated_at' => now(),
        ]);
    }

    public function all(Request $request)
    {
        $hashtags = $this->trendingService->getTrendingHashtags(5);
        $posts = $this->trendingService->getTrendingPosts(10);
        $users = $this->trendingService->getTrendingUsers(5);

        return response()->json([
            'hashtags' => $hashtags,
            'posts' => $posts,
            'users' => $users,
            'generated_at' => now(),
        ]);
    }

    public function stats()
    {
        $stats = $this->trendingService->getTrendingStats();

        return response()->json($stats);
    }

    public function refresh()
    {
        $result = $this->trendingService->updateTrendingScores();

        return response()->json([
            'message' => 'Trending data refreshed successfully',
            'result' => $result,
        ]);
    }
}
