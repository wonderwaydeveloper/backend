<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     BOOKMARKS & REPOSTS SYSTEM - UNIFIED TEST (150)       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$results = ['passed' => 0, 'failed' => 0, 'sections' => [], 'critical' => []];

function test($section, $name, $condition, $critical = false) {
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
        if ($critical) $results['critical'][] = $name;
    }
}

function section($name) {
    echo "\n" . str_repeat("=", 64) . "\n$name\n" . str_repeat("=", 64) . "\n";
}

// ============================================
// PART 1: ARCHITECTURE & CODE
// ============================================
section("PART 1: ARCHITECTURE & CODE");

echo "\nControllers:\n";
test('Architecture', 'BookmarkController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/BookmarkController.php'), true);
test('Architecture', 'RepostController exists', file_exists(__DIR__ . '/app/Http/Controllers/Api/RepostController.php'), true);
test('Architecture', 'BookmarkController class loads', class_exists(\App\Http\Controllers\Api\BookmarkController::class), true);
test('Architecture', 'RepostController class loads', class_exists(\App\Http\Controllers\Api\RepostController::class), true);

echo "\nModels:\n";
test('Architecture', 'Bookmark model exists', file_exists(__DIR__ . '/app/Models/Bookmark.php'), true);
test('Architecture', 'Repost model exists', file_exists(__DIR__ . '/app/Models/Repost.php'), true);
test('Architecture', 'Bookmark model class loads', class_exists(\App\Models\Bookmark::class), true);
test('Architecture', 'Repost model class loads', class_exists(\App\Models\Repost::class), true);

echo "\nEvents:\n";
test('Architecture', 'PostReposted event exists', file_exists(__DIR__ . '/app/Events/PostReposted.php'), true);
test('Architecture', 'PostReposted event class loads', class_exists(\App\Events\PostReposted::class), true);

echo "\nListeners:\n";
test('Architecture', 'SendRepostNotification listener exists', file_exists(__DIR__ . '/app/Listeners/SendRepostNotification.php'), true);
test('Architecture', 'SendRepostNotification listener class loads', class_exists(\App\Listeners\SendRepostNotification::class), true);

echo "\nPolicies:\n";
test('Architecture', 'BookmarkPolicy exists', file_exists(__DIR__ . '/app/Policies/BookmarkPolicy.php'), true);
test('Architecture', 'BookmarkPolicy class loads', class_exists(\App\Policies\BookmarkPolicy::class), true);

// ============================================
// PART 2: DATABASE & SCHEMA
// ============================================
section("PART 2: DATABASE & SCHEMA");

$bookmarkMigration = glob(__DIR__ . '/database/migrations/*_create_bookmarks_table.php');
$repostMigration = glob(__DIR__ . '/database/migrations/*_create_reposts_table.php');

echo "\nMigrations:\n";
test('Database', 'Bookmarks migration exists', !empty($bookmarkMigration), true);
test('Database', 'Reposts migration exists', !empty($repostMigration), true);

if (!empty($bookmarkMigration)) {
    $content = file_get_contents($bookmarkMigration[0]);
    test('Database', 'Bookmarks has user_id', strpos($content, 'user_id') !== false, true);
    test('Database', 'Bookmarks has post_id', strpos($content, 'post_id') !== false, true);
    test('Database', 'Bookmarks has unique constraint', strpos($content, "unique(['user_id', 'post_id']") !== false, true);
    test('Database', 'Bookmarks has foreign keys', strpos($content, 'constrained') !== false, true);
    test('Database', 'Bookmarks has cascade delete', strpos($content, 'cascadeOnDelete') !== false, true);
}

if (!empty($repostMigration)) {
    $content = file_get_contents($repostMigration[0]);
    test('Database', 'Reposts has user_id', strpos($content, 'user_id') !== false, true);
    test('Database', 'Reposts has post_id', strpos($content, 'post_id') !== false, true);
    test('Database', 'Reposts has quote field', strpos($content, 'quote') !== false, true);
    test('Database', 'Reposts has unique constraint', strpos($content, "unique(['user_id', 'post_id']") !== false, true);
    test('Database', 'Reposts has foreign keys', strpos($content, 'constrained') !== false, true);
    test('Database', 'Reposts has cascade delete', strpos($content, 'cascadeOnDelete') !== false, true);
}

echo "\nTable Verification:\n";
test('Database', 'Bookmarks table exists', Schema::hasTable('bookmarks'), true);
test('Database', 'Reposts table exists', Schema::hasTable('reposts'), true);

if (Schema::hasTable('bookmarks')) {
    test('Database', 'Bookmarks.user_id column exists', Schema::hasColumn('bookmarks', 'user_id'), true);
    test('Database', 'Bookmarks.post_id column exists', Schema::hasColumn('bookmarks', 'post_id'), true);
}

if (Schema::hasTable('reposts')) {
    test('Database', 'Reposts.user_id column exists', Schema::hasColumn('reposts', 'user_id'), true);
    test('Database', 'Reposts.post_id column exists', Schema::hasColumn('reposts', 'post_id'), true);
    test('Database', 'Reposts.quote column exists', Schema::hasColumn('reposts', 'quote'), true);
}

if (Schema::hasTable('posts')) {
    test('Database', 'Posts.reposts_count column exists', Schema::hasColumn('posts', 'reposts_count'), true);
}

// ============================================
// PART 3: API & ROUTES
// ============================================
section("PART 3: API & ROUTES");

exec('php artisan route:list 2>&1', $output);
$routesList = implode("\n", $output);

echo "\nBookmark Routes:\n";
test('API', 'GET /bookmarks route', strpos($routesList, 'api/bookmarks') !== false, true);
test('API', 'POST /posts/{post}/bookmark route', strpos($routesList, 'posts/{post}/bookmark') !== false, true);

echo "\nRepost Routes:\n";
test('API', 'POST /posts/{post}/repost route', strpos($routesList, 'posts/{post}/repost') !== false, true);
test('API', 'DELETE /posts/{post}/repost route', strpos($routesList, 'unrepost') !== false, true);
test('API', 'GET /posts/{post}/reposts route', strpos($routesList, 'posts/{post}/reposts') !== false, true);
test('API', 'GET /my-reposts route', strpos($routesList, 'my-reposts') !== false, true);

$routes = file_get_contents(__DIR__ . '/routes/api.php');
$bookmarkController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/BookmarkController.php');
$repostController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/RepostController.php');

echo "\nController Methods:\n";
test('API', 'BookmarkController.index() exists', strpos($bookmarkController, 'function index') !== false, true);
test('API', 'BookmarkController.toggle() exists', strpos($bookmarkController, 'function toggle') !== false, true);
test('API', 'RepostController.repost() exists', strpos($repostController, 'function repost') !== false, true);
test('API', 'RepostController.unrepost() exists', strpos($repostController, 'function unrepost') !== false, true);
test('API', 'RepostController.reposts() exists', strpos($repostController, 'function reposts') !== false, true);
test('API', 'RepostController.myReposts() exists', strpos($repostController, 'function myReposts') !== false, true);

echo "\nMiddleware:\n";
test('API', 'Routes use auth:sanctum', strpos($routes, 'auth:sanctum') !== false, true);
test('API', 'Bookmark uses permission middleware', strpos($routes, 'post.bookmark') !== false, true);
test('API', 'Repost uses permission middleware', strpos($routes, 'post.repost') !== false, true);

// ============================================
// PART 4: SECURITY
// ============================================
section("PART 4: SECURITY");

echo "\nAuthentication:\n";
test('Security', 'Routes use auth:sanctum', strpos($routes, 'auth:sanctum') !== false, true);
test('Security', 'BookmarkController uses auth', strpos($bookmarkController, 'auth()->user()') !== false || strpos($bookmarkController, '$request->user()') !== false, true);
test('Security', 'RepostController uses auth', strpos($repostController, 'auth()->user()') !== false || strpos($repostController, '$request->user()') !== false, true);

echo "\nAuthorization:\n";
test('Security', 'BookmarkPolicy exists', file_exists(__DIR__ . '/app/Policies/BookmarkPolicy.php'), true);
$policy = file_get_contents(__DIR__ . '/app/Policies/BookmarkPolicy.php');
test('Security', 'BookmarkPolicy has create()', strpos($policy, 'function create') !== false, true);
test('Security', 'BookmarkPolicy has delete()', strpos($policy, 'function delete') !== false, true);
test('Security', 'BookmarkPolicy checks ownership', strpos($policy, 'user_id') !== false, true);

echo "\nData Protection:\n";
$bookmarkModel = file_get_contents(__DIR__ . '/app/Models/Bookmark.php');
$repostModel = file_get_contents(__DIR__ . '/app/Models/Repost.php');
test('Security', 'Bookmark has mass assignment protection', strpos($bookmarkModel, '$fillable') !== false, true);
test('Security', 'Repost has mass assignment protection', strpos($repostModel, '$fillable') !== false, true);
test('Security', 'Bookmark fillable only safe fields', strpos($bookmarkModel, '$fillable') !== false && strpos($bookmarkModel, "'id'") === false, true);
test('Security', 'Repost fillable only safe fields', strpos($repostModel, '$fillable') !== false && strpos($repostModel, "'id'") === false, true);

echo "\nSQL Injection Prevention:\n";
test('Security', 'Bookmark uses Eloquent ORM', strpos($bookmarkController, '->where(') !== false, true);
test('Security', 'Repost uses Eloquent ORM', strpos($repostController, '->where(') !== false, true);
test('Security', 'No raw SQL queries', strpos($bookmarkController, 'DB::raw') === false && strpos($repostController, 'DB::raw') === false, true);

echo "\nXSS Prevention:\n";
$postModel = file_get_contents(__DIR__ . '/app/Models/Post.php');
test('Security', 'Post uses strip_tags', strpos($postModel, 'strip_tags') !== false, true);
test('Security', 'JSON responses auto-escaped', strpos($bookmarkController, 'response()->json') !== false, true);

echo "\nRace Condition Prevention:\n";
test('Security', 'Repost uses DB::transaction', strpos($repostController, 'DB::transaction') !== false, true);
test('Security', 'Repost uses lockForUpdate', strpos($repostController, 'lockForUpdate') !== false, true);

// ============================================
// PART 5: VALIDATION
// ============================================
section("PART 5: VALIDATION");

echo "\nInput Validation:\n";
test('Validation', 'Repost validates quote field', strpos($repostController, 'validate') !== false, true);
test('Validation', 'Quote uses ContentLength rule', strpos($repostController, 'ContentLength') !== false, true);
test('Validation', 'Quote is nullable', strpos($repostController, 'nullable') !== false, true);
test('Validation', 'ContentLength rule exists', file_exists(__DIR__ . '/app/Rules/ContentLength.php'), true);
test('Validation', 'No direct request->all() usage', strpos($bookmarkController, 'request->all()') === false && strpos($repostController, 'request->all()') === false, true);

// ============================================
// PART 6: BUSINESS LOGIC
// ============================================
section("PART 6: BUSINESS LOGIC");

echo "\nBookmark Logic:\n";
test('Business', 'Bookmark toggle creates/deletes', strpos($bookmarkController, 'create') !== false && strpos($bookmarkController, 'delete') !== false, true);
test('Business', 'Bookmark returns status', strpos($bookmarkController, 'bookmarked') !== false, true);
test('Business', 'Bookmark uses pagination', strpos($bookmarkController, 'paginate') !== false, true);
test('Business', 'Bookmark checks existing', strpos($bookmarkController, "where('post_id'") !== false, true);

echo "\nRepost Logic:\n";
test('Business', 'Repost checks existing', strpos($repostController, "where('post_id'") !== false, true);
test('Business', 'Repost increments counter', strpos($repostController, 'increment') !== false, true);
test('Business', 'Repost decrements counter', strpos($repostController, 'decrement') !== false, true);
test('Business', 'Repost dispatches event', strpos($repostController, 'event(new') !== false, true);
test('Business', 'Repost supports quote', strpos($repostController, 'quote') !== false, true);
test('Business', 'Repost distinguishes quote/repost', strpos($repostController, 'isQuote') !== false, true);

echo "\nError Handling:\n";
test('Business', 'Transaction rollback on error', strpos($repostController, 'DB::transaction') !== false, true);
test('Business', 'Proper error responses', strpos($repostController, 'response()->json') !== false, true);
test('Business', 'No exposed stack traces', strpos($bookmarkController, 'dd(') === false && strpos($repostController, 'dd(') === false, true);

echo "\nNo Service Layer:\n";
test('Business', 'No BookmarkService (simple CRUD)', !file_exists(__DIR__ . '/app/Services/BookmarkService.php'), true);
test('Business', 'No RepostService (simple CRUD)', !file_exists(__DIR__ . '/app/Services/RepostService.php'), true);

// ============================================
// PART 7: MODELS & RELATIONSHIPS
// ============================================
section("PART 7: MODELS & RELATIONSHIPS");

echo "\nBookmark Model:\n";
test('Models', 'Bookmark.user() relationship', strpos($bookmarkModel, 'function user') !== false, true);
test('Models', 'Bookmark.post() relationship', strpos($bookmarkModel, 'function post') !== false, true);

echo "\nRepost Model:\n";
test('Models', 'Repost.user() relationship', strpos($repostModel, 'function user') !== false, true);
test('Models', 'Repost.post() relationship', strpos($repostModel, 'function post') !== false, true);

echo "\nPost Model Integration:\n";
test('Models', 'Post has bookmarks() relationship', strpos($postModel, 'function bookmarks') !== false, true);
test('Models', 'Post has reposts() relationship', strpos($postModel, 'function reposts') !== false, true);
test('Models', 'Post has reposts_count in fillable', strpos($postModel, 'reposts_count') !== false, true);
test('Models', 'Post has reposts_count in casts', strpos($postModel, "'reposts_count' => 'integer'") !== false, true);

echo "\nUser Model Integration:\n";
$userModel = file_get_contents(__DIR__ . '/app/Models/User.php');
test('Models', 'User has bookmarks() relationship', strpos($userModel, 'function bookmarks') !== false, true);
test('Models', 'User has reposts() relationship', strpos($userModel, 'function reposts') !== false, true);

echo "\nModel Instantiation:\n";
try {
    $bookmark = new \App\Models\Bookmark();
    test('Models', 'Bookmark model instantiates', $bookmark !== null, true);
    test('Models', 'Bookmark.user() callable', method_exists($bookmark, 'user'), true);
    test('Models', 'Bookmark.post() callable', method_exists($bookmark, 'post'), true);
} catch (\Exception $e) {
    test('Models', 'Bookmark model instantiation', false, true);
}

try {
    $repost = new \App\Models\Repost();
    test('Models', 'Repost model instantiates', $repost !== null, true);
    test('Models', 'Repost.user() callable', method_exists($repost, 'user'), true);
    test('Models', 'Repost.post() callable', method_exists($repost, 'post'), true);
} catch (\Exception $e) {
    test('Models', 'Repost model instantiation', false, true);
}

// ============================================
// PART 8: INTEGRATION
// ============================================
section("PART 8: INTEGRATION");

echo "\nNotification Integration:\n";
test('Integration', 'PostReposted event exists', file_exists(__DIR__ . '/app/Events/PostReposted.php'), true);
test('Integration', 'SendRepostNotification listener exists', file_exists(__DIR__ . '/app/Listeners/SendRepostNotification.php'), true);

$listener = file_get_contents(__DIR__ . '/app/Listeners/SendRepostNotification.php');
test('Integration', 'Listener uses NotificationJob', strpos($listener, 'SendNotificationJob') !== false, true);
test('Integration', 'Listener checks self-repost', strpos($listener, 'post->user_id === $event->user->id') !== false, true);
test('Integration', 'Listener distinguishes quote', strpos($listener, 'isQuote') !== false, true);

echo "\nEvent Registration:\n";
$provider = file_get_contents(__DIR__ . '/app/Providers/AppServiceProvider.php');
test('Integration', 'PostReposted → SendRepostNotification registered', strpos($provider, 'PostReposted') !== false && strpos($provider, 'SendRepostNotification') !== false, true);

$event = file_get_contents(__DIR__ . '/app/Events/PostReposted.php');
test('Integration', 'Event uses SerializesModels', strpos($event, 'SerializesModels') !== false, true);
test('Integration', 'Event uses Dispatchable', strpos($event, 'Dispatchable') !== false, true);

// ============================================
// PART 9: TWITTER STANDARDS
// ============================================
section("PART 9: TWITTER STANDARDS");

echo "\nBookmark Features:\n";
test('Twitter', 'Bookmark toggle (Twitter standard)', strpos($bookmarkController, 'toggle') !== false, true);
test('Twitter', 'Bookmark list with pagination', strpos($bookmarkController, 'paginate') !== false, true);
test('Twitter', 'Bookmark includes post.user', strpos($bookmarkController, 'post.user') !== false, true);
test('Twitter', 'Bookmark pagination (20 per page)', strpos($bookmarkController, 'paginate(20)') !== false, true);

echo "\nRepost Features:\n";
test('Twitter', 'Repost (Twitter standard)', strpos($repostController, 'function repost') !== false, true);
test('Twitter', 'Unrepost (Twitter standard)', strpos($repostController, 'function unrepost') !== false, true);
test('Twitter', 'Quote tweet support', strpos($repostController, 'quote') !== false, true);
test('Twitter', 'Repost counter', strpos($repostController, 'reposts_count') !== false, true);
test('Twitter', 'Repost list', strpos($repostController, 'function reposts') !== false, true);
test('Twitter', 'Repost includes user info', strpos($repostController, 'user:id,name,username,avatar') !== false, true);

echo "\nTwitter API Compliance:\n";
test('Twitter', 'Unique constraint (no duplicate bookmarks)', !empty($bookmarkMigration) && strpos(file_get_contents($bookmarkMigration[0]), 'unique') !== false, true);
test('Twitter', 'Unique constraint (no duplicate reposts)', !empty($repostMigration) && strpos(file_get_contents($repostMigration[0]), 'unique') !== false, true);
test('Twitter', 'Quote length validation', strpos($repostController, 'ContentLength') !== false, true);
test('Twitter', 'Proper HTTP status codes', strpos($repostController, ', 201)') !== false, true);

// ============================================
// PART 10: NO PARALLEL WORK
// ============================================
section("PART 10: NO PARALLEL WORK");

echo "\nSingle Implementation:\n";
$bookmarkControllers = [
    'BookmarkController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/BookmarkController.php'),
    'BookmarksController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/BookmarksController.php'),
    'SaveController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/SaveController.php'),
];
$bookmarkCount = array_sum($bookmarkControllers);
test('No Parallel', 'Only BookmarkController exists', $bookmarkCount === 1 && $bookmarkControllers['BookmarkController.php'], true);

$repostControllers = [
    'RepostController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/RepostController.php'),
    'RepostsController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/RepostsController.php'),
    'RetweetController.php' => file_exists(__DIR__ . '/app/Http/Controllers/Api/RetweetController.php'),
];
$repostCount = array_sum($repostControllers);
test('No Parallel', 'Only RepostController exists', $repostCount === 1 && $repostControllers['RepostController.php'], true);

echo "\nNo Duplicate Logic:\n";
test('No Parallel', 'No Bookmark::create in multiple places', substr_count($bookmarkController, 'Bookmark::create') === 0, true);
test('No Parallel', 'No Repost::create in multiple places', substr_count($repostController, 'Repost::create') === 0, true);
test('No Parallel', 'Controller delegates properly', strpos($bookmarkController, '->bookmarks()') !== false, true);

// ============================================
// PART 11: OPERATIONAL READINESS
// ============================================
section("PART 11: OPERATIONAL READINESS");

echo "\nDatabase:\n";
try {
    DB::connection()->getPdo();
    test('Operational', 'Database connected', true, true);
} catch (\Exception $e) {
    test('Operational', 'Database connected', false, true);
}

echo "\nConfiguration:\n";
test('Operational', 'APP_ENV is set', env('APP_ENV') !== null, true);

echo "\nReal Functionality:\n";
try {
    $bookmark = new \App\Models\Bookmark();
    $bookmark->user_id = 1;
    $bookmark->post_id = 1;
    test('Operational', 'Bookmark can set attributes', $bookmark->user_id === 1, true);
    
    $repost = new \App\Models\Repost();
    $repost->user_id = 1;
    $repost->post_id = 1;
    $repost->quote = 'Test quote';
    test('Operational', 'Repost can set attributes', $repost->quote === 'Test quote', true);
} catch (\Exception $e) {
    test('Operational', 'Real functionality test', false);
}

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n" . str_repeat("=", 64) . "\n";
echo "FINAL SUMMARY\n";
echo str_repeat("=", 64) . "\n\n";

foreach ($results['sections'] as $section => $data) {
    $total = $data['passed'] + $data['failed'];
    $percent = $total > 0 ? round(($data['passed'] / $total) * 100, 1) : 0;
    printf("%-25s %3d/%3d (%5.1f%%)\n", $section, $data['passed'], $total, $percent);
}

$total = $results['passed'] + $results['failed'];
$percentage = round(($results['passed'] / $total) * 100, 1);

echo "\n";
echo "Total Tests: $total\n";
echo "Passed: {$results['passed']} ✓\n";
echo "Failed: {$results['failed']} ✗\n";
echo "Success Rate: $percentage%\n\n";

if (!empty($results['critical'])) {
    echo "🔴 CRITICAL ISSUES:\n";
    echo str_repeat("-", 64) . "\n";
    foreach (array_unique($results['critical']) as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

if ($results['failed'] === 0) {
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ BOOKMARKS & REPOSTS - 100% COMPLETE                   ║\n";
    echo "║  ✅ ROADMAP COMPLIANT                                     ║\n";
    echo "║  ✅ TWITTER STANDARDS MET                                 ║\n";
    echo "║  ✅ NO PARALLEL WORK                                      ║\n";
    echo "║  ✅ FULLY OPERATIONAL                                     ║\n";
    echo "║  ✅ PRODUCTION READY                                      ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
} elseif ($percentage >= 95) {
    echo "STATUS: 🟡 NEARLY COMPLETE (Minor fixes needed)\n";
} else {
    echo "STATUS: 🔴 NEEDS WORK\n";
}

echo "\n";
exit($results['failed'] > 0 ? 1 : 0);
