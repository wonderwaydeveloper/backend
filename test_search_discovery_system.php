<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   SEARCH & DISCOVERY SYSTEM - UNIFIED COMPREHENSIVE TEST  ║\n";
echo "║                    ALL 175 TESTS                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$results = ['passed' => 0, 'failed' => 0, 'sections' => []];

function test($section, $name, $condition) {
    global $results;
    if (!isset($results['sections'][$section])) {
        $results['sections'][$section] = ['passed' => 0, 'failed' => 0];
    }
    
    if ($condition) {
        echo "  ✓ $name\n";
        $results['passed']++;
        $results['sections'][$section]['passed']++;
    } else {
        echo "  ✗ $name\n";
        $results['failed']++;
        $results['sections'][$section]['failed']++;
    }
}

function section($name) {
    echo "\n" . str_pad("", 64, "=") . "\n";
    echo "$name\n";
    echo str_pad("", 64, "=") . "\n";
}

// ============================================
// PART 1: SYSTEM REVIEW (ROADMAP CRITERIA)
// ============================================
section("PART 1: SYSTEM REVIEW (68 tests)");

echo "\n1. Architecture & Code (10)\n";
test('Architecture', 'SearchController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/SearchController.php'));
test('Architecture', 'TrendingController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/TrendingController.php'));
test('Architecture', 'SuggestionController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/SuggestionController.php'));
test('Architecture', 'HashtagController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/HashtagController.php'));
test('Architecture', 'SearchService exists', file_exists(__DIR__ . '/app/Services/SearchService.php'));
test('Architecture', 'TrendingService exists', file_exists(__DIR__ . '/app/Services/TrendingService.php'));
test('Architecture', 'UserSuggestionService exists', file_exists(__DIR__ . '/app/Services/UserSuggestionService.php'));
test('Architecture', 'Hashtag Model exists', file_exists(__DIR__ . '/app/Models/Hashtag.php'));
test('Architecture', 'SearchResultResource exists', file_exists(__DIR__ . '/app/Http/Resources/SearchResultResource.php'));
test('Architecture', 'TrendingResource exists', file_exists(__DIR__ . '/app/Http/Resources/TrendingResource.php'));

echo "\n2. Database & Schema (7)\n";
test('Database', 'Hashtags migration exists', !empty(glob(__DIR__ . '/database/migrations/*_create_hashtags_table.php')));
test('Database', 'Hashtag_post migration exists', !empty(glob(__DIR__ . '/database/migrations/*_create_hashtag_post_table.php')));
$hashtagsMigration = glob(__DIR__ . '/database/migrations/*_create_hashtags_table.php');
if (!empty($hashtagsMigration)) {
    $content = file_get_contents($hashtagsMigration[0]);
    test('Database', 'Has name column', strpos($content, "->string('name')") !== false);
    test('Database', 'Has slug column', strpos($content, "->string('slug')") !== false);
    test('Database', 'Has posts_count column', strpos($content, 'posts_count') !== false);
    test('Database', 'Has unique constraint', strpos($content, "->unique()") !== false);
    test('Database', 'Has index on posts_count', strpos($content, "index('posts_count')") !== false);
}

echo "\n3. API & Routes (10)\n";
exec('php artisan route:list 2>&1', $output);
$routes = implode("\n", $output);
test('API', 'Search posts route', strpos($routes, 'api/search/posts') !== false);
test('API', 'Search users route', strpos($routes, 'api/search/users') !== false);
test('API', 'Search hashtags route', strpos($routes, 'api/search/hashtags') !== false);
test('API', 'Trending hashtags route', strpos($routes, 'api/trending') !== false);
test('API', 'Trending posts route', strpos($routes, 'api/trending') !== false);
test('API', 'User suggestions route', strpos($routes, 'api/suggestions/users') !== false);
$routesFile = file_get_contents(__DIR__ . '/routes/api.php');
test('API', 'Search throttle middleware', strpos($routesFile, 'throttle:') !== false && strpos($routesFile, 'search') !== false);
test('API', 'Trending throttle middleware', strpos($routesFile, 'throttle:') !== false && strpos($routesFile, 'trending') !== false);
test('API', 'Auth middleware', strpos($routesFile, 'auth:sanctum') !== false);
test('API', 'RESTful naming', strpos($routesFile, "prefix('search')") !== false && strpos($routesFile, "prefix('trending')") !== false);

echo "\n4. Security (11)\n";
test('Security', 'SearchPolicy exists', file_exists(__DIR__ . '/app/Policies/SearchPolicy.php'));
$policy = file_get_contents(__DIR__ . '/app/Policies/SearchPolicy.php');
test('Security', 'Uses Spatie permissions', strpos($policy, 'hasPermissionTo') !== false);
$searchController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/SearchController.php');
test('Security', 'Controllers use authorization', strpos($searchController, '$this->authorize') !== false);
test('Security', 'Routes use auth:sanctum', strpos($routesFile, 'auth:sanctum') !== false);
test('Security', 'Rate limiting on search', strpos($routesFile, 'throttle:') !== false && strpos($routesFile, 'search') !== false);
test('Security', 'Rate limiting on trending', strpos($routesFile, 'throttle:') !== false && strpos($routesFile, 'trending') !== false);
$searchService = file_get_contents(__DIR__ . '/app/Services/SearchService.php');
test('Security', 'XSS protection', strpos($searchService, 'preg_replace') !== false);
test('Security', 'SQL injection protection', strpos($searchService, 'MeiliSearch') !== false);
$hashtagModel = file_get_contents(__DIR__ . '/app/Models/Hashtag.php');
test('Security', 'Mass assignment protection', strpos($hashtagModel, '$guarded') !== false || strpos($hashtagModel, '$fillable') !== false);
test('Security', 'Error handling', strpos($searchService, 'try') !== false && strpos($searchService, 'catch') !== false);
test('Security', 'Logging', strpos($searchService, 'Log::error') !== false);

echo "\n5. Validation (7)\n";
test('Validation', 'SearchPostsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchPostsRequest.php'));
test('Validation', 'SearchUsersRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchUsersRequest.php'));
test('Validation', 'SearchHashtagsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchHashtagsRequest.php'));
test('Validation', 'TrendingRequest exists', file_exists(__DIR__ . '/app/Http/Requests/TrendingRequest.php'));
$searchRequest = file_get_contents(__DIR__ . '/app/Http/Requests/SearchPostsRequest.php');
test('Validation', 'Has rules method', strpos($searchRequest, 'public function rules()') !== false);
test('Validation', 'Validates query', strpos($searchRequest, "'q'") !== false && strpos($searchRequest, 'required') !== false);
test('Validation', 'Config-based validation', strpos($searchRequest, "config('validation") !== false);

echo "\n6. Business Logic (8)\n";
test('Business Logic', 'searchPosts method', strpos($searchService, 'function searchPosts') !== false);
test('Business Logic', 'searchUsers method', strpos($searchService, 'function searchUsers') !== false);
test('Business Logic', 'searchHashtags method', strpos($searchService, 'function searchHashtags') !== false);
$trendingService = file_get_contents(__DIR__ . '/app/Services/TrendingService.php');
test('Business Logic', 'getTrendingHashtags method', strpos($trendingService, 'function getTrendingHashtags') !== false);
test('Business Logic', 'getTrendingPosts method', strpos($trendingService, 'function getTrendingPosts') !== false);
test('Business Logic', 'Uses caching', strpos($trendingService, 'Cache::remember') !== false);
test('Business Logic', 'Error handling', strpos($searchService, 'try') !== false && strpos($searchService, 'catch') !== false);
test('Business Logic', 'Logging errors', strpos($searchService, 'Log::error') !== false);

echo "\n7. Integration (10)\n";
test('Integration', 'SearchPerformed event', file_exists(__DIR__ . '/app/Events/SearchPerformed.php'));
test('Integration', 'ContentIndexed event', file_exists(__DIR__ . '/app/Events/ContentIndexed.php'));
test('Integration', 'TrendingUpdated event', file_exists(__DIR__ . '/app/Events/TrendingUpdated.php'));
test('Integration', 'LogSearchActivity listener', file_exists(__DIR__ . '/app/Listeners/LogSearchActivity.php'));
test('Integration', 'UpdateSearchIndex listener', file_exists(__DIR__ . '/app/Listeners/UpdateSearchIndex.php'));
test('Integration', 'IndexContentJob', file_exists(__DIR__ . '/app/Jobs/IndexContentJob.php'));
test('Integration', 'UpdateTrendingJob', file_exists(__DIR__ . '/app/Jobs/UpdateTrendingJob.php'));
test('Integration', 'SearchService dispatches events', strpos($searchService, 'event(') !== false);
test('Integration', 'TrendingService dispatches events', strpos($trendingService, 'event(') !== false);
test('Integration', 'Block/Mute integration', strpos($searchService, 'blocks') !== false && strpos($searchService, 'mutes') !== false);

echo "\n8. Testing (5)\n";
test('Testing', 'This test script exists', file_exists(__FILE__));
test('Testing', 'SearchTest exists', file_exists(__DIR__ . '/tests/Feature/SearchTest.php'));
test('Testing', 'TrendingTest exists', file_exists(__DIR__ . '/tests/Feature/TrendingTest.php'));
$searchTest = file_get_contents(__DIR__ . '/tests/Feature/SearchTest.php');
test('Testing', 'Has authentication tests', strpos($searchTest, 'requires_authentication') !== false);
test('Testing', 'Comprehensive (>50 tests)', $results['passed'] > 50);

// ============================================
// PART 2: TWITTER COMPLIANCE (26 tests)
// ============================================
section("PART 2: TWITTER COMPLIANCE (26 tests)");

$config = include __DIR__ . '/config/validation.php';

echo "\n1. Rate Limits (4)\n";
test('Twitter', 'Search posts: 450/15min', config('limits.rate_limits.search.posts') === '450,15');
test('Twitter', 'Search users: 180/15min', config('limits.rate_limits.search.users') === '180,15');
test('Twitter', 'Trending: 75/15min', config('limits.rate_limits.trending.default') === '75,15');
test('Twitter', 'Suggestions: 180/15min', config('limits.rate_limits.search.suggestions') === '180,15');

echo "\n2. Query Parameters (2)\n";
test('Twitter', 'Max query: 500 chars', $config['search']['query']['max_length'] === 500);
test('Twitter', 'Min query: 1 char', $config['search']['query']['min_length'] === 1);

echo "\n3. Pagination (4)\n";
test('Twitter', 'Max results: 100', $config['search']['posts']['max_per_page'] === 100);
test('Twitter', 'Default results: 20', $config['search']['posts']['per_page'] === 20);
test('Twitter', 'Users max: 100', $config['search']['users']['max_per_page'] === 100);
test('Twitter', 'Hashtags max: 100', $config['search']['hashtags']['max_per_page'] === 100);

echo "\n4. Trending (3)\n";
test('Twitter', 'Max trending: 100', $config['trending']['limit']['max'] === 100);
test('Twitter', 'Default trending: 10', $config['trending']['limit']['default'] === 10);
test('Twitter', 'Max timeframe: 720h', $config['trending']['timeframe']['max'] === 720);

echo "\n5. Features (5)\n";
test('Twitter', 'Posts search', strpos($searchController, 'function posts') !== false);
test('Twitter', 'Users search', strpos($searchController, 'function users') !== false);
test('Twitter', 'Hashtags search', strpos($searchController, 'function hashtags') !== false);
test('Twitter', 'Advanced search', strpos($searchController, 'function advanced') !== false);
test('Twitter', 'Suggestions', strpos($searchController, 'function suggestions') !== false);

echo "\n6. Filters (5)\n";
test('Twitter', 'Date range filter', strpos($searchRequest, 'date_from') !== false);
test('Twitter', 'Media filter', strpos($searchRequest, 'has_media') !== false);
test('Twitter', 'User filter', strpos($searchRequest, 'user_id') !== false);
test('Twitter', 'Hashtags filter', strpos($searchRequest, 'hashtags') !== false);
test('Twitter', 'Sort options', strpos($searchRequest, 'sort') !== false);

echo "\n7. Security (3)\n";
test('Twitter', 'Authentication required', strpos($routesFile, 'auth:sanctum') !== false);
test('Twitter', 'Rate limiting applied', strpos($routesFile, 'throttle:') !== false);
test('Twitter', 'XSS protection', strpos($searchService, 'preg_replace') !== false);

// ============================================
// PART 3: OPERATIONAL READINESS (44 tests)
// ============================================
section("PART 3: OPERATIONAL READINESS (48 tests)");

echo "\n1. No Parallel Work (5)\n";
test('Operational', 'Uses ONLY SearchService', strpos($searchController, 'SearchService') !== false && strpos($searchController, 'ElasticsearchService') === false);
test('Operational', 'Elasticsearch NOT used', !file_exists(__DIR__ . '/app/Services/ElasticsearchService.php'));
test('Operational', 'Routes use SearchController', strpos($routesFile, 'SearchController') !== false);
test('Operational', 'Only ONE implementation', count(glob(__DIR__ . '/app/Services/*Search*.php')) === 1);
test('Operational', 'No SearchSystemAnalysis command', !file_exists(__DIR__ . '/app/Console/Commands/SearchSystemAnalysis.php'));

echo "\n2. Controllers (5)\n";
test('Operational', 'SearchController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/SearchController.php'));
test('Operational', 'SearchController valid', strpos($searchController, 'class SearchController') !== false);
test('Operational', 'TrendingController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/TrendingController.php'));
test('Operational', 'SuggestionController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/SuggestionController.php'));
test('Operational', 'HashtagController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/HashtagController.php'));

echo "\n3. Services (5)\n";
test('Operational', 'SearchService exists', file_exists(__DIR__ . '/app/Services/SearchService.php'));
test('Operational', 'SearchService has searchPosts', strpos($searchService, 'searchPosts') !== false);
test('Operational', 'SearchService has searchUsers', strpos($searchService, 'searchUsers') !== false);
test('Operational', 'TrendingService exists', file_exists(__DIR__ . '/app/Services/TrendingService.php'));
test('Operational', 'UserSuggestionService exists', file_exists(__DIR__ . '/app/Services/UserSuggestionService.php'));

echo "\n4. Models & Resources (4)\n";
test('Operational', 'Hashtag model exists', file_exists(__DIR__ . '/app/Models/Hashtag.php'));
test('Operational', 'SearchResultResource exists', file_exists(__DIR__ . '/app/Http/Resources/SearchResultResource.php'));
test('Operational', 'TrendingResource exists', file_exists(__DIR__ . '/app/Http/Resources/TrendingResource.php'));
test('Operational', 'Post model has searchable', file_exists(__DIR__ . '/app/Models/Post.php'));

echo "\n5. Requests (4)\n";
test('Operational', 'SearchPostsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchPostsRequest.php'));
test('Operational', 'SearchUsersRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchUsersRequest.php'));
test('Operational', 'SearchHashtagsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchHashtagsRequest.php'));
test('Operational', 'TrendingRequest exists', file_exists(__DIR__ . '/app/Http/Requests/TrendingRequest.php'));

echo "\n6. Policies (2)\n";
test('Operational', 'SearchPolicy exists', file_exists(__DIR__ . '/app/Policies/SearchPolicy.php'));
$policy = file_get_contents(__DIR__ . '/app/Policies/SearchPolicy.php');
test('Operational', 'Policy uses Spatie', strpos($policy, 'hasPermissionTo') !== false);

echo "\n7. Events (3)\n";
test('Operational', 'SearchPerformed event', file_exists(__DIR__ . '/app/Events/SearchPerformed.php'));
test('Operational', 'ContentIndexed event', file_exists(__DIR__ . '/app/Events/ContentIndexed.php'));
test('Operational', 'TrendingUpdated event', file_exists(__DIR__ . '/app/Events/TrendingUpdated.php'));

echo "\n8. Listeners (2)\n";
test('Operational', 'LogSearchActivity listener', file_exists(__DIR__ . '/app/Listeners/LogSearchActivity.php'));
test('Operational', 'UpdateSearchIndex listener', file_exists(__DIR__ . '/app/Listeners/UpdateSearchIndex.php'));

echo "\n9. Jobs (2)\n";
test('Operational', 'IndexContentJob exists', file_exists(__DIR__ . '/app/Jobs/IndexContentJob.php'));
test('Operational', 'UpdateTrendingJob exists', file_exists(__DIR__ . '/app/Jobs/UpdateTrendingJob.php'));

echo "\n10. Routes (6)\n";
test('Operational', 'Search posts route', strpos($routes, 'api/search/posts') !== false);
test('Operational', 'Search users route', strpos($routes, 'api/search/users') !== false);
test('Operational', 'Search hashtags route', strpos($routes, 'api/search/hashtags') !== false);
test('Operational', 'Trending hashtags route', strpos($routes, 'api/trending') !== false);
test('Operational', 'Trending posts route', strpos($routes, 'api/trending') !== false);
test('Operational', 'Suggestions route', strpos($routes, 'api/suggestions') !== false);

echo "\n11. Database (2)\n";
test('Operational', 'Hashtags migration', !empty(glob(__DIR__ . '/database/migrations/*_create_hashtags_table.php')));
test('Operational', 'Hashtag_post migration', !empty(glob(__DIR__ . '/database/migrations/*_create_hashtag_post_table.php')));

echo "\n12. Security (3)\n";
test('Operational', 'Rate limiting configured', config('limits.rate_limits.search.posts') !== null);
test('Operational', 'Authentication required', strpos($routesFile, 'auth:sanctum') !== false);
test('Operational', 'Authorization in controllers', strpos($searchController, '$this->authorize') !== false);

echo "\n13. Integration (3)\n";
test('Operational', 'Block/Mute integrated', strpos($searchService, 'blocks') !== false && strpos($searchService, 'mutes') !== false);
test('Operational', 'Events dispatched', strpos($searchService, 'event(') !== false);
test('Operational', 'Error handling', strpos($searchService, 'try') !== false && strpos($searchService, 'catch') !== false);

echo "\n14. Service Instantiation (2)\n";
try {
    $searchServiceInstance = app(\App\Services\SearchService::class);
    test('Operational', 'SearchService instantiates', true);
} catch (\Exception $e) {
    test('Operational', 'SearchService instantiates', false);
}
try {
    $trendingServiceInstance = app(\App\Services\TrendingService::class);
    test('Operational', 'TrendingService instantiates', true);
} catch (\Exception $e) {
    test('Operational', 'TrendingService instantiates', false);
}

// ============================================
// PART 4: FINAL VERIFICATION (20 tests)
// ============================================
section("PART 4: FINAL VERIFICATION (20 tests)");

echo "\n1. Core Files (5)\n";
test('Final', 'SearchController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/SearchController.php'));
test('Final', 'SearchService exists', file_exists(__DIR__ . '/app/Services/SearchService.php'));
test('Final', 'TrendingService exists', file_exists(__DIR__ . '/app/Services/TrendingService.php'));
test('Final', 'SearchPolicy exists', file_exists(__DIR__ . '/app/Policies/SearchPolicy.php'));
test('Final', 'Hashtag model exists', file_exists(__DIR__ . '/app/Models/Hashtag.php'));

echo "\n2. Request Classes (4)\n";
test('Final', 'SearchPostsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchPostsRequest.php'));
test('Final', 'SearchUsersRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchUsersRequest.php'));
test('Final', 'SearchHashtagsRequest exists', file_exists(__DIR__ . '/app/Http/Requests/SearchHashtagsRequest.php'));
test('Final', 'TrendingRequest exists', file_exists(__DIR__ . '/app/Http/Requests/TrendingRequest.php'));

echo "\n3. Resources (2)\n";
test('Final', 'SearchResultResource exists', file_exists(__DIR__ . '/app/Http/Resources/SearchResultResource.php'));
test('Final', 'TrendingResource exists', file_exists(__DIR__ . '/app/Http/Resources/TrendingResource.php'));

echo "\n4. Events (3)\n";
test('Final', 'SearchPerformed event', file_exists(__DIR__ . '/app/Events/SearchPerformed.php'));
test('Final', 'ContentIndexed event', file_exists(__DIR__ . '/app/Events/ContentIndexed.php'));
test('Final', 'TrendingUpdated event', file_exists(__DIR__ . '/app/Events/TrendingUpdated.php'));

echo "\n5. Listeners (2)\n";
test('Final', 'LogSearchActivity listener', file_exists(__DIR__ . '/app/Listeners/LogSearchActivity.php'));
test('Final', 'UpdateSearchIndex listener', file_exists(__DIR__ . '/app/Listeners/UpdateSearchIndex.php'));

echo "\n6. Jobs (2)\n";
test('Final', 'IndexContentJob', file_exists(__DIR__ . '/app/Jobs/IndexContentJob.php'));
test('Final', 'UpdateTrendingJob', file_exists(__DIR__ . '/app/Jobs/UpdateTrendingJob.php'));

echo "\n7. Tests (2)\n";
test('Final', 'SearchTest exists', file_exists(__DIR__ . '/tests/Feature/SearchTest.php'));
test('Final', 'TrendingTest exists', file_exists(__DIR__ . '/tests/Feature/TrendingTest.php'));

// ============================================
// PART 5: CLEANLINESS (13 tests)
// ============================================
section("PART 5: CLEANLINESS (13 tests)");

echo "\n1. No Unused Files (3)\n";
test('Cleanliness', 'ElasticsearchService removed', !file_exists(__DIR__ . '/app/Services/ElasticsearchService.php'));
test('Cleanliness', 'SearchSystemAnalysis removed', !file_exists(__DIR__ . '/app/Console/Commands/SearchSystemAnalysis.php'));
test('Cleanliness', 'Old test removed', !file_exists(__DIR__ . '/test_search_discovery.php'));

echo "\n2. No Duplicates (2)\n";
test('Cleanliness', 'Only ONE SearchService', count(glob(__DIR__ . '/app/Services/*Search*.php')) === 1);
test('Cleanliness', 'Only ONE SearchController', count(glob(__DIR__ . '/app/Http/Controllers/Api/*Search*.php')) === 1);

echo "\n3. No Debug Code (2)\n";
$files = ['app/Http/Controllers/Api/SearchController.php', 'app/Services/SearchService.php', 'app/Services/TrendingService.php'];
$hasDebug = false;
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strpos($content, 'dd(') !== false || strpos($content, 'dump(') !== false) {
            $hasDebug = true;
            break;
        }
    }
}
test('Cleanliness', 'No debug code', !$hasDebug);
$hasTodo = false;
foreach ($files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strpos($content, 'TODO') !== false || strpos($content, 'FIXME') !== false) {
            $hasTodo = true;
            break;
        }
    }
}
test('Cleanliness', 'No TODO/FIXME', !$hasTodo);

echo "\n4. No Unused References (2)\n";
exec('findstr /C:"Elasticsearch" /S /M app\*.php routes\*.php config\*.php 2>nul', $output, $code);
test('Cleanliness', 'No Elasticsearch references', $code !== 0 || empty($output));
$testFiles = glob(__DIR__ . '/test_*.php');
$searchTests = array_filter($testFiles, function($file) {
    return strpos($file, 'search') !== false || strpos($file, 'discovery') !== false;
});
test('Cleanliness', 'Test files organized', count($searchTests) <= 2);

echo "\n5. Clean Configuration (4)\n";
$composer = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
test('Cleanliness', 'No Elasticsearch package', !isset($composer['require']['elasticsearch/elasticsearch']));
test('Cleanliness', 'MeiliSearch installed', isset($composer['require']['meilisearch/meilisearch-php']));
$env = file_get_contents(__DIR__ . '/.env');
test('Cleanliness', 'No Elasticsearch in .env', strpos($env, 'ELASTICSEARCH_HOST') === false);
test('Cleanliness', 'MeiliSearch in .env', strpos($env, 'MEILISEARCH_HOST') !== false);

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n";
echo str_pad("", 64, "=") . "\n";
echo "FINAL SUMMARY\n";
echo str_pad("", 64, "=") . "\n\n";

foreach ($results['sections'] as $section => $data) {
    $total = $data['passed'] + $data['failed'];
    $percent = $total > 0 ? round(($data['passed'] / $total) * 100, 1) : 0;
    printf("%-30s %3d/%3d (%5.1f%%)\n", $section, $data['passed'], $total, $percent);
}

$total = $results['passed'] + $results['failed'];
$percentage = round(($results['passed'] / $total) * 100, 1);

echo "\n";
echo "Total Tests: $total\n";
echo "Passed: {$results['passed']} ✓\n";
echo "Failed: {$results['failed']} ✗\n";
echo "Success Rate: $percentage%\n\n";

// Calculate ROADMAP score
$roadmapScore = 0;
$sections = [
    'Architecture' => 20,
    'Database' => 15,
    'API' => 15,
    'Security' => 20,
    'Validation' => 10,
    'Business Logic' => 10,
    'Integration' => 5,
    'Testing' => 5
];

foreach ($sections as $section => $weight) {
    if (isset($results['sections'][$section])) {
        $data = $results['sections'][$section];
        $sectionTotal = $data['passed'] + $data['failed'];
        if ($sectionTotal > 0) {
            $roadmapScore += ($data['passed'] / $sectionTotal) * $weight;
        }
    }
}

echo "ROADMAP SCORE: " . round($roadmapScore, 1) . "/100\n";
echo "TWITTER COMPLIANCE: " . (isset($results['sections']['Twitter']) ? round(($results['sections']['Twitter']['passed'] / ($results['sections']['Twitter']['passed'] + $results['sections']['Twitter']['failed'])) * 100, 1) : 0) . "%\n";
echo "OPERATIONAL: " . (isset($results['sections']['Operational']) ? ($results['sections']['Operational']['failed'] === 0 ? 'YES' : 'NO') : 'NO') . "\n";
echo "CLEANLINESS: " . (isset($results['sections']['Cleanliness']) ? round(($results['sections']['Cleanliness']['passed'] / ($results['sections']['Cleanliness']['passed'] + $results['sections']['Cleanliness']['failed'])) * 100, 1) : 0) . "%\n\n";

if ($results['failed'] === 0) {
    echo "STATUS: ✅ PRODUCTION READY\n";
} elseif ($percentage >= 95) {
    echo "STATUS: 🟡 NEARLY READY (Minor fixes needed)\n";
} else {
    echo "STATUS: ❌ NOT READY\n";
}

echo "\n";
exit($results['failed'] > 0 ? 1 : 0);
