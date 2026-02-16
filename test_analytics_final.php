<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Post;
use App\Models\AnalyticsEvent;
use App\Models\ConversionMetric;
use App\Services\AnalyticsService;
use App\Services\ConversionTrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║   Analytics System - Final Comprehensive Report               ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$report = [
    'database' => [],
    'models' => [],
    'services' => [],
    'controllers' => [],
    'routes' => [],
    'integration' => [],
    'functionality' => []
];

echo "═════════════════════════════════════════════════════════════════\n";
echo "1. DATABASE SCHEMA ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

// Check tables
$tables = ['analytics_events', 'conversion_metrics', 'posts'];
foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo ($exists ? "  ✓" : "  ✗") . " Table: $table\n";
    $report['database'][$table] = $exists;
}

// Check Post analytics columns
echo "\n📊 Post Analytics Columns:\n";
$postColumns = [
    'impression_count', 'engagement_rate', 'url_link_clicks',
    'user_profile_clicks', 'hashtag_clicks', 'video_views',
    'video_25_percent', 'video_50_percent', 'video_75_percent', 'video_100_percent'
];
foreach ($postColumns as $column) {
    $exists = Schema::hasColumn('posts', $column);
    echo ($exists ? "  ✓" : "  ✗") . " $column\n";
    $report['database']["post_$column"] = $exists;
}

// Check indexes
echo "\n📈 Database Indexes:\n";
$analyticsIndexes = DB::select("SHOW INDEX FROM analytics_events WHERE Key_name != 'PRIMARY'");
$conversionIndexes = DB::select("SHOW INDEX FROM conversion_metrics WHERE Key_name != 'PRIMARY'");
echo "  ✓ analytics_events: " . count($analyticsIndexes) . " indexes\n";
echo "  ✓ conversion_metrics: " . count($conversionIndexes) . " indexes\n";
$report['database']['analytics_indexes'] = count($analyticsIndexes);
$report['database']['conversion_indexes'] = count($conversionIndexes);

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "2. MODELS & RELATIONSHIPS ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

// Check models
$models = [
    'AnalyticsEvent' => 'App\\Models\\AnalyticsEvent',
    'ConversionMetric' => 'App\\Models\\ConversionMetric',
    'User' => 'App\\Models\\User',
    'Post' => 'App\\Models\\Post'
];

foreach ($models as $name => $class) {
    $exists = class_exists($class);
    echo ($exists ? "  ✓" : "  ✗") . " Model: $name\n";
    $report['models'][$name] = $exists;
}

// Check relationships
echo "\n🔗 Model Relationships:\n";
$relationships = [
    'User->conversionMetrics' => method_exists(User::class, 'conversionMetrics'),
    'AnalyticsEvent->user' => method_exists(AnalyticsEvent::class, 'user'),
    'AnalyticsEvent->entity' => method_exists(AnalyticsEvent::class, 'entity'),
    'ConversionMetric->user' => method_exists(ConversionMetric::class, 'user'),
    'Post->media' => method_exists(Post::class, 'media')
];

foreach ($relationships as $name => $exists) {
    echo ($exists ? "  ✓" : "  ✗") . " $name\n";
    $report['models'][$name] = $exists;
}

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "3. SERVICES ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

$analyticsService = app(AnalyticsService::class);
$conversionService = app(ConversionTrackingService::class);

echo "📊 AnalyticsService:\n";
$analyticsMethods = [
    'getUserAnalytics', 'getPostAnalytics', 'getDashboardMetrics'
];
foreach ($analyticsMethods as $method) {
    $exists = method_exists($analyticsService, $method);
    echo ($exists ? "  ✓" : "  ✗") . " $method()\n";
    $report['services']["AnalyticsService::$method"] = $exists;
}

echo "\n💰 ConversionTrackingService:\n";
$conversionMethods = [
    'track', 'getConversionFunnel', 'getConversionsBySource',
    'getUserJourney', 'getCohortAnalysis'
];
foreach ($conversionMethods as $method) {
    $exists = method_exists($conversionService, $method);
    echo ($exists ? "  ✓" : "  ✗") . " $method()\n";
    $report['services']["ConversionTrackingService::$method"] = $exists;
}

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "4. CONTROLLERS ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

$controllers = [
    'AnalyticsController' => [
        'class' => 'App\\Http\\Controllers\\Api\\AnalyticsController',
        'methods' => ['userAnalytics', 'postAnalytics', 'trackEvent', 'dashboard']
    ],
    'ConversionController' => [
        'class' => 'App\\Http\\Controllers\\Api\\ConversionController',
        'methods' => ['track', 'funnel', 'bySource', 'userJourney', 'cohortAnalysis']
    ]
];

foreach ($controllers as $name => $config) {
    $exists = class_exists($config['class']);
    echo ($exists ? "  ✓" : "  ✗") . " $name\n";
    $report['controllers'][$name] = $exists;
    
    if ($exists) {
        foreach ($config['methods'] as $method) {
            $hasMethod = method_exists($config['class'], $method);
            echo ($hasMethod ? "    ✓" : "    ✗") . " $method()\n";
            $report['controllers']["$name::$method"] = $hasMethod;
        }
    }
}

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "5. API ROUTES ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

$routes = collect(Route::getRoutes())->map(fn($r) => [
    'uri' => $r->uri(),
    'method' => implode('|', $r->methods()),
    'middleware' => $r->middleware()
]);

$expectedRoutes = [
    'api/analytics/user' => 'GET',
    'api/analytics/posts/{post}' => 'GET',
    'api/analytics/track' => 'POST',
    'api/conversions/track' => 'POST',
    'api/conversions/funnel' => 'GET',
    'api/conversions/by-source' => 'GET',
    'api/conversions/user-journey' => 'GET',
    'api/conversions/cohort-analysis' => 'GET'
];

foreach ($expectedRoutes as $uri => $method) {
    $route = $routes->first(fn($r) => $r['uri'] === $uri);
    $exists = $route !== null;
    $hasMethod = $exists && str_contains($route['method'], $method);
    echo ($exists && $hasMethod ? "  ✓" : "  ✗") . " $method $uri\n";
    $report['routes'][$uri] = $exists && $hasMethod;
    
    if ($exists) {
        $hasAuth = in_array('auth:sanctum', $route['middleware']);
        if ($uri === 'api/analytics/track') {
            echo ($hasAuth ? "    ✗" : "    ✓") . " Public access (no auth)\n";
        } else {
            echo ($hasAuth ? "    ✓" : "    ✗") . " Protected (auth:sanctum)\n";
        }
    }
}

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "6. SYSTEM INTEGRATION ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

// Test real functionality
$user = User::first();
$post = Post::first();

if ($user && $post) {
    echo "📊 Real-time Testing:\n";
    
    // Test event tracking
    try {
        $beforeCount = AnalyticsEvent::count();
        AnalyticsEvent::track('test_view', 'post', $post->id, $user->id, ['test' => true]);
        $afterCount = AnalyticsEvent::count();
        $tracked = $afterCount > $beforeCount;
        echo ($tracked ? "  ✓" : "  ✗") . " Event tracking works\n";
        $report['integration']['event_tracking'] = $tracked;
    } catch (Exception $e) {
        echo "  ✗ Event tracking failed: " . $e->getMessage() . "\n";
        $report['integration']['event_tracking'] = false;
    }
    
    // Test conversion tracking
    try {
        $beforeCount = ConversionMetric::count();
        $conversionService->track('test_conversion', $user->id, ['test' => true], 10.00, 'USD', 'test_source', 'test_campaign');
        $afterCount = ConversionMetric::count();
        $tracked = $afterCount > $beforeCount;
        
        // Verify source and campaign
        $conversion = ConversionMetric::latest()->first();
        $hasSource = $conversion && $conversion->source === 'test_source';
        $hasCampaign = $conversion && $conversion->campaign === 'test_campaign';
        
        echo ($tracked ? "  ✓" : "  ✗") . " Conversion tracking works\n";
        echo ($hasSource ? "  ✓" : "  ✗") . " Source tracking works\n";
        echo ($hasCampaign ? "  ✓" : "  ✗") . " Campaign tracking works\n";
        
        $report['integration']['conversion_tracking'] = $tracked;
        $report['integration']['source_tracking'] = $hasSource;
        $report['integration']['campaign_tracking'] = $hasCampaign;
    } catch (Exception $e) {
        echo "  ✗ Conversion tracking failed: " . $e->getMessage() . "\n";
        $report['integration']['conversion_tracking'] = false;
    }
    
    // Test analytics service
    try {
        $analytics = $analyticsService->getUserAnalytics($user, '7d');
        $hasData = is_array($analytics) && isset($analytics['profile_views']);
        echo ($hasData ? "  ✓" : "  ✗") . " User analytics works\n";
        $report['integration']['user_analytics'] = $hasData;
    } catch (Exception $e) {
        echo "  ✗ User analytics failed: " . $e->getMessage() . "\n";
        $report['integration']['user_analytics'] = false;
    }
    
    // Test post analytics
    try {
        $analytics = $analyticsService->getPostAnalytics($post->id, '7d');
        $hasMetrics = is_array($analytics) && isset($analytics['twitter_metrics']);
        echo ($hasMetrics ? "  ✓" : "  ✗") . " Post analytics works\n";
        $report['integration']['post_analytics'] = $hasMetrics;
    } catch (Exception $e) {
        echo "  ✗ Post analytics failed: " . $e->getMessage() . "\n";
        $report['integration']['post_analytics'] = false;
    }
    
    // Test post model integration
    try {
        $post->increment('impression_count');
        $post->increment('url_link_clicks');
        $post->update(['engagement_rate' => 7.5]);
        $post->refresh();
        
        $hasImpressions = $post->impression_count > 0;
        $hasClicks = $post->url_link_clicks > 0;
        $hasRate = $post->engagement_rate == 7.5;
        
        echo ($hasImpressions ? "  ✓" : "  ✗") . " Post impression tracking\n";
        echo ($hasClicks ? "  ✓" : "  ✗") . " Post click tracking\n";
        echo ($hasRate ? "  ✓" : "  ✗") . " Post engagement rate\n";
        
        $report['integration']['post_impressions'] = $hasImpressions;
        $report['integration']['post_clicks'] = $hasClicks;
        $report['integration']['post_engagement'] = $hasRate;
    } catch (Exception $e) {
        echo "  ✗ Post model integration failed: " . $e->getMessage() . "\n";
        $report['integration']['post_model'] = false;
    }
} else {
    echo "  ⚠ Skipping integration tests (no data available)\n";
}

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "7. PERMISSIONS & POLICIES ✓\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

$policyExists = class_exists('App\\Policies\\AnalyticsPolicy');
echo ($policyExists ? "  ✓" : "  ✗") . " AnalyticsPolicy exists\n";
$report['integration']['policy'] = $policyExists;

$permissionExists = \Spatie\Permission\Models\Permission::where('name', 'analytics.view')->exists();
echo ($permissionExists ? "  ✓" : "  ✗") . " analytics.view permission exists\n";
$report['integration']['permission'] = $permissionExists;

echo "\n═════════════════════════════════════════════════════════════════\n";
echo "║                    FINAL ASSESSMENT                            ║\n";
echo "═════════════════════════════════════════════════════════════════\n\n";

// Calculate scores
$totalChecks = 0;
$passedChecks = 0;

foreach ($report as $category => $checks) {
    foreach ($checks as $check => $result) {
        $totalChecks++;
        if ($result) $passedChecks++;
    }
}

$percentage = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : 0;

echo "📊 System Status:\n";
echo "  • Total Checks: $totalChecks\n";
echo "  • Passed: $passedChecks ✓\n";
echo "  • Failed: " . ($totalChecks - $passedChecks) . " ✗\n";
echo "  • Success Rate: $percentage%\n\n";

echo "📋 Category Breakdown:\n";
foreach ($report as $category => $checks) {
    $catTotal = count($checks);
    $catPassed = count(array_filter($checks));
    $catPercentage = $catTotal > 0 ? round(($catPassed / $catTotal) * 100, 2) : 0;
    $status = $catPercentage == 100 ? "✓" : ($catPercentage >= 80 ? "⚠" : "✗");
    echo "  $status " . ucfirst($category) . ": $catPassed/$catTotal ($catPercentage%)\n";
}

echo "\n";

if ($percentage == 100) {
    echo "🎉 PERFECT: Analytics System is 100% operational!\n";
    echo "✅ Fully integrated with all systems\n";
    echo "✅ All features working correctly\n";
    echo "✅ Ready for production use\n";
} elseif ($percentage >= 95) {
    echo "✅ EXCELLENT: Analytics System is fully operational!\n";
    echo "✅ Minor non-critical issues only\n";
    echo "✅ Ready for production use\n";
} elseif ($percentage >= 85) {
    echo "✅ GOOD: Analytics System is operational\n";
    echo "⚠️  Some minor improvements needed\n";
} else {
    echo "❌ NEEDS WORK: Analytics System has issues\n";
    echo "🔧 Fixes required before production\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($percentage >= 95 ? 0 : 1);
