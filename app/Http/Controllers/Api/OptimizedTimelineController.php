<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\CacheOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedTimelineController extends Controller
{
    public function __construct(
        private CacheOptimizationService $cacheService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $cacheKey = "timeline:{$user->id}:" . ($request->get('page', 1));
        
        return Cache::tags(['timeline', "user:{$user->id}"])
            ->remember($cacheKey, 300, function () use ($user, $request) {
                return $this->getOptimizedTimeline($user, $request->get('page', 1));
            });
    }

    private function getOptimizedTimeline($user, $page = 1)
    {
        // Single query to get following IDs with cache
        $followingIds = $this->cacheService->getCachedFollowingIds($user->id);
        
        // Optimized timeline query - single query with minimal relations
        $posts = Post::select([
                'id', 'user_id', 'content', 'image', 'gif_url', 
                'quoted_post_id', 'created_at'
            ])
            ->with([
                'user:id,name,username,avatar'
            ])
            ->withCount(['likes', 'comments'])
            ->whereIn('user_id', $followingIds)
            ->where('is_draft', false)
            ->latest('created_at')
            ->paginate(20, ['*'], 'page', $page);

        // Batch check likes for current user - single query
        $postIds = $posts->pluck('id')->toArray();
        $userLikes = [];
        
        if (!empty($postIds)) {
            $userLikes = DB::table('likes')
                ->where('user_id', $user->id)
                ->whereIn('likeable_id', $postIds)
                ->where('likeable_type', Post::class)
                ->pluck('likeable_id')
                ->toArray();
        }

        // Add like status to posts
        $posts->getCollection()->transform(function ($post) use ($userLikes) {
            $post->is_liked = in_array($post->id, $userLikes);
            return $post;
        });

        return [
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'optimized' => true,
                'cached' => false
            ]
        ];
    }

    public function liveTimeline(Request $request)
    {
        $user = $request->user();
        
        // Use cached following IDs
        $followingIds = $this->cacheService->getCachedFollowingIds($user->id);

        // Optimized live timeline - last 2 hours only
        $posts = Post::select([
                'id', 'user_id', 'content', 'image', 'created_at'
            ])
            ->with(['user:id,name,username,avatar'])
            ->withCount(['likes', 'comments'])
            ->whereIn('user_id', $followingIds)
            ->where('created_at', '>', now()->subHours(2))
            ->published()
            ->latest('created_at')
            ->limit(30)
            ->get();

        return response()->json([
            'posts' => $posts,
            'channels' => [
                'timeline' => 'timeline',
                'user_timeline' => 'user.timeline.' . $user->id,
            ],
            'optimized' => true
        ]);
    }
}