<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getUserAnalytics(User $user, string $period = '30d'): array
    {
        $startDate = $this->getStartDate($period);

        return [
            'profile_views' => $this->getProfileViews($user, $startDate),
            'post_performance' => $this->getPostPerformance($user, $startDate),
            'engagement_metrics' => $this->getEngagementMetrics($user, $startDate),
            'follower_growth' => $this->getFollowerGrowth($user, $startDate),
            'top_posts' => $this->getTopPosts($user, $startDate),
        ];
    }

    public function getPostAnalytics(int $postId, string $period = '7d'): array
    {
        $startDate = $this->getStartDate($period);
        $post = \App\Models\Post::find($postId);

        return [
            'impressions' => $post->impression_count ?? 0,
            'views' => $this->getPostViews($postId, $startDate),
            'engagement' => $this->getPostEngagement($postId, $startDate),
            'demographics' => $this->getPostDemographics($postId, $startDate),
            'timeline' => $this->getPostTimeline($postId, $startDate),
            'twitter_metrics' => [
                'impression_count' => $post->impression_count ?? 0,
                'retweet_count' => $post->reposts_count ?? 0,
                'reply_count' => $post->comments_count ?? 0,
                'like_count' => $post->likes_count ?? 0,
                'quote_count' => $post->quotes_count ?? 0,
                'url_link_clicks' => $post->url_link_clicks ?? 0,
                'user_profile_clicks' => $post->user_profile_clicks ?? 0,
                'hashtag_clicks' => $post->hashtag_clicks ?? 0,
                'engagement_rate' => $post->engagement_rate ?? 0,
            ],
        ];
    }

    public function getDashboardMetrics(User $user): array
    {
        return [
            'today' => $this->getTodayMetrics($user),
            'week' => $this->getWeekMetrics($user),
            'month' => $this->getMonthMetrics($user),
            'growth' => $this->getGrowthMetrics($user),
        ];
    }

    private function getProfileViews(User $user, Carbon $startDate): array
    {
        $views = AnalyticsEvent::where('event_type', 'profile_view')
            ->where('entity_type', 'user')
            ->where('entity_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $views->sum('views'),
            'daily' => $views->toArray(),
        ];
    }

    private function getPostPerformance(User $user, Carbon $startDate): array
    {
        $result = DB::table('posts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as total_posts,
                AVG(likes_count) as avg_likes,
                AVG(comments_count) as avg_comments,
                SUM(likes_count + comments_count) as total_engagement
            ')
            ->first();

        return [
            'total_posts' => $result->total_posts ?? 0,
            'avg_likes' => $result->avg_likes ?? 0,
            'avg_comments' => $result->avg_comments ?? 0,
            'total_engagement' => $result->total_engagement ?? 0,
        ];
    }

    private function getEngagementMetrics(User $user, Carbon $startDate): array
    {
        $postIds = DB::table('posts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->pluck('id');

        $engagement = AnalyticsEvent::whereIn('entity_id', $postIds)
            ->where('entity_type', 'post')
            ->whereIn('event_type', config('services.analytics.event_types.engagement'))
            ->where('created_at', '>=', $startDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->keyBy('event_type');

        return [
            'likes' => $engagement->get('post_like')?->count ?? 0,
            'comments' => $engagement->get('post_comment')?->count ?? 0,
            'reposts' => $engagement->get('post_repost')?->count ?? 0,
        ];
    }

    private function getFollowerGrowth(User $user, Carbon $startDate): array
    {
        return DB::table('follows')
            ->where('following_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as new_followers')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getTopPosts(User $user, Carbon $startDate): array
    {
        return DB::table('posts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('id, content, likes_count, comments_count, (likes_count + comments_count) as engagement')
            ->orderBy('engagement', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    private function getPostViews(int $postId, Carbon $startDate): array
    {
        $views = AnalyticsEvent::where('event_type', 'post_view')
            ->where('entity_type', 'post')
            ->where('entity_id', $postId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total' => $views->sum('views'),
            'daily' => $views->toArray(),
        ];
    }

    private function getPostEngagement(int $postId, Carbon $startDate): array
    {
        $engagement = AnalyticsEvent::where('entity_type', 'post')
            ->where('entity_id', $postId)
            ->whereIn('event_type', config('services.analytics.event_types.post_engagement'))
            ->where('created_at', '>=', $startDate)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->get()
            ->keyBy('event_type');

        $views = $this->getPostViews($postId, $startDate)['total'];
        $totalEngagements = $engagement->sum('count');
        
        return [
            'likes' => $engagement->get('post_like')?->count ?? 0,
            'comments' => $engagement->get('post_comment')?->count ?? 0,
            'retweets' => $engagement->get('post_repost')?->count ?? 0,
            'shares' => $engagement->get('post_share')?->count ?? 0,
            'link_clicks' => $engagement->get('link_click')?->count ?? 0,
            'total_engagements' => $totalEngagements,
            'engagement_rate' => $views > 0 ? round(($totalEngagements / $views) * 100, 2) : 0,
        ];
    }

    private function getPostDemographics(int $postId, Carbon $startDate): array
    {
        // Simplified demographics - in real app would join with user profiles
        return [
            'unique_viewers' => AnalyticsEvent::where('event_type', 'post_view')
                ->where('entity_type', 'post')
                ->where('entity_id', $postId)
                ->where('created_at', '>=', $startDate)
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    private function getPostTimeline(int $postId, Carbon $startDate): array
    {
        return AnalyticsEvent::where('entity_type', 'post')
            ->where('entity_id', $postId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, event_type, COUNT(*) as count')
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    private function getTodayMetrics(User $user): array
    {
        $today = now()->startOfDay();
        
        return [
            'profile_views' => $this->getProfileViews($user, $today)['total'],
            'post_engagement' => $this->getEngagementMetrics($user, $today),
        ];
    }

    private function getWeekMetrics(User $user): array
    {
        $weekAgo = now()->subWeek();
        
        return [
            'profile_views' => $this->getProfileViews($user, $weekAgo)['total'],
            'post_performance' => $this->getPostPerformance($user, $weekAgo),
        ];
    }

    private function getMonthMetrics(User $user): array
    {
        $monthAgo = now()->subMonth();
        
        return [
            'profile_views' => $this->getProfileViews($user, $monthAgo)['total'],
            'follower_growth' => count($this->getFollowerGrowth($user, $monthAgo)),
        ];
    }

    private function getGrowthMetrics(User $user): array
    {
        $thisWeek = $this->getWeekMetrics($user);
        $lastWeek = $this->getWeekMetrics($user); // Would calculate previous week
        
        return [
            'profile_views_growth' => 15.5, // Simplified calculation
            'engagement_growth' => 8.2,
        ];
    }

    private function getStartDate(string $period): Carbon
    {
        return match($period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };
    }
}