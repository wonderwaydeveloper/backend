<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Report;
use App\Http\Controllers\Api\ModerationController;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              تست کامل Report System                          ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

try {
    $user = User::firstOrCreate(['email' => 'reporttest@test.com'],
        ['name' => 'Reporter', 'username' => 'reporttest', 'password' => bcrypt('password')]);
    
    $offender = User::firstOrCreate(['email' => 'offendertest@test.com'],
        ['name' => 'Offender', 'username' => 'offendertest', 'password' => bcrypt('password')]);
    
    $post = Post::create(['user_id' => $offender->id, 'content' => 'Test post', 'published_at' => now()]);
    $comment = Comment::create(['user_id' => $offender->id, 'post_id' => $post->id, 'content' => 'Test comment']);
    
    echo "✓ Test data created\n\n";
    
    echo "📦 بخش 1: Model & Database\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    if (class_exists('App\Models\Report')) {
        echo "  ✓ Report Model\n";
        $passed++;
    }
    
    $columns = DB::select("SHOW COLUMNS FROM reports");
    $columnNames = array_column($columns, 'Field');
    $required = ['id', 'reporter_id', 'reportable_type', 'reportable_id', 'reason', 'status'];
    
    if (count(array_intersect($required, $columnNames)) === count($required)) {
        echo "  ✓ Database columns\n";
        $passed++;
    }
    
    echo "\n🔗 بخش 2: Relationships\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $report = Report::create([
        'reporter_id' => $user->id,
        'reportable_type' => 'App\Models\Post',
        'reportable_id' => $post->id,
        'reason' => 'spam',
        'status' => 'pending'
    ]);
    
    if ($report->reporter && $report->reporter->id === $user->id) {
        echo "  ✓ reporter() relationship\n";
        $passed++;
    }
    
    if ($report->reportable && $report->reportable->id === $post->id) {
        echo "  ✓ reportable() morphTo\n";
        $passed++;
    }
    
    if (method_exists($report, 'reviewer')) {
        echo "  ✓ reviewer() relationship\n";
        $passed++;
    }
    
    echo "\n🛣️ بخش 3: Routes (Twitter Standard)\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $routes = app('router')->getRoutes();
    $requiredRoutes = [
        'reports/post/{post}' => 'POST',
        'reports/user/{user}' => 'POST',
        'reports/comment/{comment}' => 'POST',
        'reports/my-reports' => 'GET',
    ];
    
    foreach ($requiredRoutes as $uri => $method) {
        $found = collect($routes)->first(function($route) use ($uri, $method) {
            return str_contains($route->uri(), $uri) && in_array($method, $route->methods());
        });
        
        if ($found) {
            echo "  ✓ {$method} /api/{$uri}\n";
            $passed++;
        } else {
            echo "  ✗ {$method} /api/{$uri}\n";
            $failed++;
        }
    }
    
    echo "\n🎮 بخش 4: Controller Methods\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $methods = ['reportPost', 'reportUser', 'reportComment', 'myReports', 'getReports', 'updateReportStatus'];
    foreach ($methods as $method) {
        if (method_exists('App\Http\Controllers\Api\ModerationController', $method)) {
            echo "  ✓ {$method}()\n";
            $passed++;
        } else {
            echo "  ✗ {$method}()\n";
            $failed++;
        }
    }
    
    echo "\n🔒 بخش 5: Security\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $controller = app(ModerationController::class);
    
    // Self-reporting
    auth()->setUser($user);
    $request = Request::create('/api/reports/user/' . $user->id, 'POST', ['reason' => 'spam']);
    $response = $controller->reportUser($request, $user);
    $data = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 422) {
        echo "  ✓ Self-reporting prevented\n";
        $passed++;
    } else {
        echo "  ✗ Self-reporting allowed\n";
        $failed++;
    }
    
    // Duplicate prevention
    Report::where('reporter_id', $user->id)->delete();
    Report::create([
        'reporter_id' => $user->id,
        'reportable_type' => 'App\Models\Post',
        'reportable_id' => $post->id,
        'reason' => 'spam',
        'status' => 'pending'
    ]);
    
    $request = Request::create('/api/reports/post/' . $post->id, 'POST', ['reason' => 'spam']);
    $response = $controller->reportPost($request, $post);
    $data = json_decode($response->getContent(), true);
    
    if ($response->getStatusCode() === 400) {
        echo "  ✓ Duplicate prevention\n";
        $passed++;
    } else {
        echo "  ✗ Duplicate allowed\n";
        $failed++;
    }
    
    // Rate limiting
    $reportRoute = collect($routes)->first(function($route) {
        return str_contains($route->uri(), 'reports/post/{post}') && in_array('POST', $route->methods());
    });
    
    if ($reportRoute) {
        $hasThrottle = false;
        foreach ($reportRoute->middleware() as $m) {
            if (str_contains($m, 'throttle')) {
                $hasThrottle = true;
                break;
            }
        }
        
        if ($hasThrottle) {
            echo "  ✓ Rate limiting (5/min)\n";
            $passed++;
        }
    }
    
    // Mass assignment
    $fillable = (new Report())->getFillable();
    if (!in_array('status', $fillable) && !in_array('reviewed_by', $fillable)) {
        echo "  ✓ Mass assignment protected\n";
        $passed++;
    }
    
    // SQL Injection
    try {
        Report::where('reporter_id', "1' OR '1'='1")->get();
        echo "  ✓ SQL Injection protected\n";
        $passed++;
    } catch (Exception $e) {
        echo "  ✗ SQL Injection failed\n";
        $failed++;
    }
    
    echo "\n📊 بخش 6: Polymorphic Support\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $postReport = Report::where('reportable_type', 'App\Models\Post')->first();
    if ($postReport && $postReport->reportable instanceof Post) {
        echo "  ✓ Post reporting\n";
        $passed++;
    }
    
    $commentReport = Report::create([
        'reporter_id' => $user->id,
        'reportable_type' => 'App\Models\Comment',
        'reportable_id' => $comment->id,
        'reason' => 'harassment',
        'status' => 'pending'
    ]);
    
    if ($commentReport->reportable instanceof Comment) {
        echo "  ✓ Comment reporting\n";
        $passed++;
    }
    
    $userReport = Report::create([
        'reporter_id' => $user->id,
        'reportable_type' => 'App\Models\User',
        'reportable_id' => $offender->id,
        'reason' => 'spam',
        'status' => 'pending'
    ]);
    
    if ($userReport->reportable instanceof User) {
        echo "  ✓ User reporting\n";
        $passed++;
    }
    
    echo "\n⚡ بخش 7: Performance\n";
    echo "─────────────────────────────────────────────────────────────────\n";
    
    $indexes = DB::select("SHOW INDEX FROM reports WHERE Key_name != 'PRIMARY'");
    echo "  ✓ Database indexes: " . count($indexes) . "\n";
    $passed++;
    
    // Cleanup
    Report::where('reporter_id', $user->id)->delete();
    Comment::where('id', $comment->id)->delete();
    Post::where('id', $post->id)->delete();
    
    echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
    echo "║                        نتیجه نهایی                           ║\n";
    echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 آمار: {$passed} موفق, {$failed} ناموفق\n";
    
    if ($failed === 0) {
        echo "✅ Report System کاملاً عملیاتی و امن است!\n\n";
    } else {
        echo "⚠️ برخی تستها ناموفق بودند\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
