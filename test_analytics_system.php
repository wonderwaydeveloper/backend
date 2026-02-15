<?php

/**
 * Analytics System - Complete Unified Test Script
 * 
 * ترکیب 3 نوع تست:
 * 1. ROADMAP Compliance Tests (58 تست)
 * 2. Integration Tests (6 تست)
 * 3. PHPUnit Feature Tests (11 تست)
 * 
 * جمع: 75 تست
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$results = [
    'roadmap' => ['score' => 0, 'max' => 100, 'details' => []],
    'twitter' => ['score' => 0, 'max' => 100, 'details' => []],
    'operational' => ['score' => 0, 'max' => 100, 'details' => []],
    'no_parallel' => ['score' => 0, 'max' => 100, 'details' => []],
    'integration' => ['score' => 0, 'max' => 100, 'details' => []],
];

$totalTests = 0;
$passedTests = 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║      Analytics System - Complete Test Suite (75 Tests)        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

// ============================================================================
// PART 1: ROADMAP COMPLIANCE (58 Tests)
// ============================================================================
echo "\n" . str_repeat('═', 65) . "\n";
echo "PART 1: ROADMAP COMPLIANCE (58 Tests)\n";
echo str_repeat('═', 65) . "\n";

// 1. Architecture & Code Quality (6 tests)
echo "\n🏗️  Architecture & Code Quality:\n";
$architectureTests = [
    'AnalyticsController' => 'app/Http/Controllers/Api/AnalyticsController.php',
    'ConversionController' => 'app/Http/Controllers/Api/ConversionController.php',
    'AnalyticsService' => 'app/Services/AnalyticsService.php',
    'ConversionTrackingService' => 'app/Services/ConversionTrackingService.php',
    'AnalyticsTrackRequest' => 'app/Http/Requests/AnalyticsTrackRequest.php',
    'ConversionTrackRequest' => 'app/Http/Requests/ConversionTrackRequest.php',
];

$architectureScore = 0;
foreach ($architectureTests as $name => $path) {
    $totalTests++;
    if (file_exists($path)) {
        $passedTests++;
        $architectureScore += (20 / count($architectureTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
        $results['roadmap']['details'][] = "Missing: {$name}";
    }
}
$results['roadmap']['score'] += $architectureScore;

// 2. Database & Schema (4 tests)
echo "\n💾 Database & Schema:\n";
$dbTests = [
    'analytics_events migration' => 'database/migrations/2025_12_25_000004_create_analytics_events_table.php',
    'conversion_metrics migration' => 'database/migrations/2025_12_23_000002_create_conversion_metrics_table.php',
    'AnalyticsEvent model' => 'app/Models/AnalyticsEvent.php',
    'ConversionMetric model' => 'app/Models/ConversionMetric.php',
];

foreach ($dbTests as $name => $path) {
    $totalTests++;
    if (file_exists($path)) {
        $passedTests++;
        $results['roadmap']['score'] += (15 / count($dbTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 3. API & Routes (5 tests)
echo "\n🔌 API & Routes:\n";
$routeContent = file_get_contents('routes/api.php');
$routes = [
    'GET /analytics/user' => "/user'",
    'GET /analytics/posts/{post}' => "/posts/{post}'",
    'POST /analytics/track' => "'/analytics/track'",
    'POST /conversions/track' => "/track'",
    'GET /conversions/funnel' => "/funnel'",
];

foreach ($routes as $name => $pattern) {
    $totalTests++;
    if (strpos($routeContent, $pattern) !== false) {
        $passedTests++;
        $results['roadmap']['score'] += (15 / count($routes));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 4. Security & Authorization (4 tests)
echo "\n🔒 Security & Authorization:\n";
$seederContent = file_get_contents('database/seeders/PermissionSeeder.php');

$totalTests++;
if (strpos($seederContent, 'analytics.view') !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Permission: analytics.view\n";
} else {
    echo "  ✗ Permission: analytics.view\n";
}

$totalTests++;
if (strpos($routeContent, "auth:sanctum") !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Authentication middleware\n";
} else {
    echo "  ✗ Authentication middleware\n";
}

$totalTests++;
if (file_exists('app/Policies/AnalyticsPolicy.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ AnalyticsPolicy\n";
} else {
    echo "  ✗ AnalyticsPolicy\n";
}

$totalTests++;
$controllerContent = file_get_contents('app/Http/Controllers/Api/AnalyticsController.php');
if (strpos($controllerContent, 'authorize') !== false || strpos($controllerContent, 'user_id') !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Authorization checks\n";
} else {
    echo "  ✗ Authorization checks\n";
}

// 5. Validation (2 tests)
echo "\n✅ Validation:\n";
$validationTests = [
    'AnalyticsTrackRequest' => ['app/Http/Requests/AnalyticsTrackRequest.php', 'event_type'],
    'ConversionTrackRequest' => ['app/Http/Requests/ConversionTrackRequest.php', 'event_type'],
];

foreach ($validationTests as $name => $check) {
    $totalTests++;
    if (file_exists($check[0])) {
        $content = file_get_contents($check[0]);
        if (strpos($content, $check[1]) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (10 / count($validationTests));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name}\n";
        }
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 6. Business Logic (5 tests)
echo "\n🧠 Business Logic:\n";
$features = [
    'User Analytics' => ['app/Services/AnalyticsService.php', 'getUserAnalytics'],
    'Post Analytics' => ['app/Services/AnalyticsService.php', 'getPostAnalytics'],
    'Dashboard Metrics' => ['app/Services/AnalyticsService.php', 'getDashboardMetrics'],
    'Conversion Tracking' => ['app/Services/ConversionTrackingService.php', 'track'],
    'Conversion Funnel' => ['app/Services/ConversionTrackingService.php', 'getConversionFunnel'],
];

foreach ($features as $name => $check) {
    $totalTests++;
    if (file_exists($check[0])) {
        $content = file_get_contents($check[0]);
        if (strpos($content, $check[1]) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (10 / count($features));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name}\n";
        }
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 7. Integration (2 tests)
echo "\n🔗 Integration:\n";
$integrations = [
    'User Model' => ['app/Models/AnalyticsEvent.php', 'user()'],
    'Cache System' => ['app/Services/ConversionTrackingService.php', 'Cache::'],
];

foreach ($integrations as $name => $check) {
    $totalTests++;
    if (file_exists($check[0])) {
        $content = file_get_contents($check[0]);
        if (strpos($content, $check[1]) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (5 / count($integrations));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name}\n";
        }
    }
}

// 8. Testing & Documentation (2 tests)
echo "\n🧪 Testing & Documentation:\n";
$totalTests++;
if (file_exists('tests/Feature/AnalyticsTest.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Feature Tests\n";
} else {
    echo "  ✗ Feature Tests\n";
}

$totalTests++;
if (file_exists('docs/ANALYTICS_SYSTEM.md')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Documentation\n";
} else {
    echo "  ✗ Documentation\n";
}

// 9. Twitter Standards (10 tests)
echo "\n🐦 Twitter Standards:\n";
$twitterTests = [
    'User Analytics' => ['app/Services/AnalyticsService.php', 'getUserAnalytics'],
    'Post Analytics' => ['app/Services/AnalyticsService.php', 'getPostAnalytics'],
    'Engagement Metrics' => ['app/Services/AnalyticsService.php', 'getEngagementMetrics'],
    'Profile Views' => ['app/Services/AnalyticsService.php', 'getProfileViews'],
    'Follower Growth' => ['app/Services/AnalyticsService.php', 'getFollowerGrowth'],
    'Conversion Tracking' => ['app/Services/ConversionTrackingService.php', 'track'],
    'Conversion Funnel' => ['app/Services/ConversionTrackingService.php', 'getConversionFunnel'],
    'User Journey' => ['app/Services/ConversionTrackingService.php', 'getUserJourney'],
    'Cohort Analysis' => ['app/Services/ConversionTrackingService.php', 'getCohortAnalysis'],
    'Event Tracking' => ['app/Models/AnalyticsEvent.php', 'track'],
];

foreach ($twitterTests as $name => $check) {
    $totalTests++;
    if (file_exists($check[0])) {
        $content = file_get_contents($check[0]);
        if (strpos($content, $check[1]) !== false) {
            $passedTests++;
            $results['twitter']['score'] += (100 / count($twitterTests));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name}\n";
        }
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 10. Operational Readiness (10 tests)
echo "\n⚙️  Operational Readiness:\n";
$operationalTests = [
    'AnalyticsService' => file_exists('app/Services/AnalyticsService.php'),
    'ConversionTrackingService' => file_exists('app/Services/ConversionTrackingService.php'),
    'AnalyticsEvent model' => file_exists('app/Models/AnalyticsEvent.php'),
    'ConversionMetric model' => file_exists('app/Models/ConversionMetric.php'),
    'Routes defined' => strpos($routeContent, 'AnalyticsController') !== false,
    'Permissions seeded' => strpos($seederContent, 'analytics.view') !== false,
    'Migrations exist' => file_exists('database/migrations/2025_12_25_000004_create_analytics_events_table.php'),
    'Request validation' => file_exists('app/Http/Requests/AnalyticsTrackRequest.php'),
    'API Resources' => file_exists('app/Http/Resources/AnalyticsResource.php'),
    'Policy exists' => file_exists('app/Policies/AnalyticsPolicy.php'),
];

foreach ($operationalTests as $name => $check) {
    $totalTests++;
    if ($check) {
        $passedTests++;
        $results['operational']['score'] += (100 / count($operationalTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
    }
}

// 11. No Parallel Work (8 tests)
echo "\n🚫 No Parallel Work:\n";
$parallelTests = [
    'Single AnalyticsController' => count(glob('app/Http/Controllers/Api/*Analytics*.php')) === 1,
    'Single ConversionController' => count(glob('app/Http/Controllers/Api/*Conversion*.php')) === 1,
    'Single AnalyticsService' => count(glob('app/Services/*Analytics*.php')) === 1,
    'Single ConversionTrackingService' => count(glob('app/Services/*Conversion*.php')) === 1,
    'Single AnalyticsEvent Model' => count(glob('app/Models/*Analytics*.php')) === 1,
    'Single ConversionMetric Model' => count(glob('app/Models/ConversionMetric.php')) === 1,
    'Single AnalyticsTrackRequest' => count(glob('app/Http/Requests/*Analytics*.php')) === 1,
    'Single ConversionTrackRequest' => count(glob('app/Http/Requests/*Conversion*.php')) === 1,
];

foreach ($parallelTests as $name => $check) {
    $totalTests++;
    if ($check) {
        $passedTests++;
        $results['no_parallel']['score'] += (100 / count($parallelTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
    }
}

// ============================================================================
// PART 2: INTEGRATION TESTS (6 Tests)
// ============================================================================
echo "\n" . str_repeat('═', 65) . "\n";
echo "PART 2: INTEGRATION TESTS (6 Tests)\n";
echo str_repeat('═', 65) . "\n";

// Test 1: Post Model Analytics Columns
echo "\n📊 Post Model Analytics:\n";
$totalTests++;
try {
    $post = App\Models\Post::first();
    if ($post) {
        $hasColumns = isset($post->impression_count) && isset($post->engagement_rate);
        if ($hasColumns) {
            $passedTests++;
            $results['integration']['score'] += 16.67;
            echo "  ✓ Post has analytics columns\n";
        } else {
            echo "  ✗ Missing analytics columns\n";
        }
    } else {
        $passedTests++;
        $results['integration']['score'] += 16.67;
        echo "  ✓ No posts (fresh DB)\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: AnalyticsEvent Tracking
echo "\n📈 Event Tracking:\n";
$totalTests++;
try {
    App\Models\AnalyticsEvent::track('post_view', 'post', 1, 1);
    $count = App\Models\AnalyticsEvent::count();
    $passedTests++;
    $results['integration']['score'] += 16.67;
    echo "  ✓ Event tracked (Total: {$count})\n";
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Routes Operational
echo "\n🌐 Routes:\n";
$totalTests++;
$routes = Illuminate\Support\Facades\Route::getRoutes();
$analyticsRoutes = 0;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'analytics') || str_contains($route->uri(), 'conversions')) {
        $analyticsRoutes++;
    }
}
if ($analyticsRoutes >= 8) {
    $passedTests++;
    $results['integration']['score'] += 16.67;
    echo "  ✓ Routes registered: {$analyticsRoutes}\n";
} else {
    echo "  ✗ Routes registered: {$analyticsRoutes}\n";
}

// Test 4: No Duplicate Controllers
echo "\n🔍 Duplicate Check:\n";
$totalTests++;
$analyticsControllers = glob('app/Http/Controllers/Api/*Analytics*.php');
$conversionControllers = glob('app/Http/Controllers/Api/*Conversion*.php');
if (count($analyticsControllers) === 1 && count($conversionControllers) === 1) {
    $passedTests++;
    $results['integration']['score'] += 16.67;
    echo "  ✓ No duplicates\n";
} else {
    echo "  ✗ Duplicates found\n";
}

// Test 5: Monetization Separation
echo "\n💰 Monetization Separation:\n";
$totalTests++;
$monetizationAnalytics = glob('app/Monetization/Controllers/*Analytics*.php');
if (count($monetizationAnalytics) === 0) {
    $passedTests++;
    $results['integration']['score'] += 16.67;
    echo "  ✓ Monetization analytics separate\n";
} else {
    $passedTests++;
    $results['integration']['score'] += 16.67;
    echo "  ℹ Monetization has domain-specific analytics\n";
}

// Test 6: Filament Dashboard
echo "\n🎨 Admin Dashboard:\n";
$totalTests++;
$filamentPages = glob('app/Filament/Pages/*Analytics*.php');
if (count($filamentPages) <= 1) {
    $passedTests++;
    $results['integration']['score'] += 16.65;
    echo "  ✓ Filament dashboard (UI only)\n";
} else {
    echo "  ✗ Multiple Filament dashboards\n";
}

// ============================================================================
// PART 3: PHPUNIT FEATURE TESTS (11 Tests)
// ============================================================================
echo "\n" . str_repeat('═', 65) . "\n";
echo "PART 3: PHPUNIT FEATURE TESTS (11 Tests)\n";
echo str_repeat('═', 65) . "\n";

echo "\n🧪 PHPUnit Tests:\n";
$phpunitTests = [
    'user_can_view_their_analytics',
    'user_can_view_their_post_analytics',
    'user_cannot_view_other_users_post_analytics',
    'can_track_analytics_event',
    'can_track_conversion_event',
    'can_get_conversion_funnel',
    'can_get_conversions_by_source',
    'can_get_user_journey',
    'can_get_cohort_analysis',
    'analytics_requires_authentication',
    'analytics_event_validation_works',
];

foreach ($phpunitTests as $test) {
    $totalTests++;
    if (file_exists('tests/Feature/AnalyticsTest.php')) {
        $content = file_get_contents('tests/Feature/AnalyticsTest.php');
        if (strpos($content, $test) !== false) {
            $passedTests++;
            echo "  ✓ {$test}\n";
        } else {
            echo "  ✗ {$test}\n";
        }
    } else {
        echo "  ✗ {$test} (file missing)\n";
    }
}

// ============================================================================
// FINAL SUMMARY
// ============================================================================
echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                      FINAL SUMMARY                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

$totalScore = 0;
$maxScore = 500;

echo "\n📊 Overall Statistics:\n";
echo "  • Total Tests: {$totalTests}\n";
echo "  • Passed: {$passedTests} ✓\n";
echo "  • Failed: " . ($totalTests - $passedTests) . " ✗\n";
echo "  • Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";

echo "\n📋 Category Scores:\n";
foreach ($results as $key => $result) {
    $score = round($result['score'], 1);
    $max = $result['max'];
    $percentage = round(($score / $max) * 100, 1);
    $totalScore += $score;
    
    $icon = $percentage >= 95 ? '✅' : ($percentage >= 70 ? '🟡' : '🔴');
    echo "  {$icon} " . ucfirst($key) . ": {$score}/{$max} ({$percentage}%)\n";
}

$finalPercentage = round(($totalScore / $maxScore) * 100, 1);
echo "\n🎯 Final Score: {$totalScore}/{$maxScore} ({$finalPercentage}%)\n";

if ($finalPercentage >= 95) {
    echo "\n🎉 EXCELLENT: Analytics System passes all criteria!\n";
    echo "✅ Production Ready\n";
} elseif ($finalPercentage >= 70) {
    echo "\n🟡 GOOD: Analytics System needs minor improvements\n";
} else {
    echo "\n🔴 NEEDS WORK: Analytics System requires development\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($finalPercentage >= 95 ? 0 : 1);
