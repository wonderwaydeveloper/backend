<?php

namespace App\Contracts\Services;

interface AnalyticsServiceInterface
{
    public function trackEvent(string $event, array $data, ?int $userId = null): bool;
    
    public function getUserAnalytics(int $userId): array;
    
    public function getPostAnalytics(int $postId): array;
    
    public function getDashboardMetrics(int $userId): array;
    
    public function getEngagementStats(int $userId, string $period = '30d'): array;
    
    public function getTopPosts(int $userId, int $limit = 10): array;
    
    public function getAudienceInsights(int $userId): array;
    
    public function exportAnalytics(int $userId, string $format = 'json'): string;
}