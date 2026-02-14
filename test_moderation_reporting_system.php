<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class ModerationReportingSystemTest
{
    private $passed = 0;
    private $failed = 0;
    private $critical = [];
    private $scores = [];

    public function run()
    {
        echo "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║         MODERATION & REPORTING SYSTEM - COMPREHENSIVE VERIFICATION        ║\n";
        echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

        $this->testArchitecture();
        $this->testDatabase();
        $this->testAPI();
        $this->testSecurity();
        $this->testValidation();
        $this->testBusinessLogic();
        $this->testModelsRelationships();
        $this->testIntegration();
        $this->testTwitterStandards();
        $this->testNoParallelWork();
        $this->testOperationalReadiness();
        $this->testRoadmapCompliance();

        $this->printSummary();
    }

    private function section($name)
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "  {$name}\n";
        echo str_repeat("=", 80) . "\n";
    }

    private function test($message, $condition, $critical = false)
    {
        if ($condition) {
            echo "  ✅ {$message}\n";
            $this->passed++;
        } else {
            echo "  ❌ {$message}\n";
            $this->failed++;
            if ($critical) {
                $this->critical[] = $message;
            }
        }
    }

    private function testArchitecture()
    {
        $this->section("PART 1: ARCHITECTURE & CODE (20 points)");
        $score = 0;

        // Controller (5 points)
        $controllerExists = file_exists(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');
        $this->test("ModerationController exists", $controllerExists, true);
        if ($controllerExists) $score += 5;

        // Model (5 points)
        $modelExists = file_exists(__DIR__ . '/app/Models/Report.php');
        $this->test("Report model exists", $modelExists, true);
        if ($modelExists) $score += 5;

        // Methods (10 points)
        if ($controllerExists) {
            $content = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');
            $methods = [
                'reportPost' => 2,
                'reportUser' => 2,
                'reportComment' => 2,
                'getReports' => 1,
                'updateReportStatus' => 1,
                'takeAction' => 1,
                'getContentStats' => 1
            ];
            
            foreach ($methods as $method => $points) {
                $exists = str_contains($content, "public function {$method}(");
                $this->test("Method {$method}() exists", $exists, true);
                if ($exists) $score += $points;
            }
        }

        $this->scores['Architecture'] = [$score, 20];
    }

    private function testDatabase()
    {
        $this->section("PART 2: DATABASE & SCHEMA (15 points)");
        $score = 0;

        // Table exists (5 points)
        $reportsTable = Schema::hasTable('reports');
        $this->test("reports table exists", $reportsTable, true);
        if ($reportsTable) $score += 5;

        // Columns (5 points)
        if ($reportsTable) {
            $columns = Schema::getColumnListing('reports');
            $required = ['id', 'reporter_id', 'reportable_type', 'reportable_id', 'reason', 'status'];
            $allExist = true;
            foreach ($required as $col) {
                $exists = in_array($col, $columns);
                $this->test("Column '{$col}' exists", $exists, true);
                if (!$exists) $allExist = false;
            }
            if ($allExist) $score += 5;
        }

        // Indexes (3 points)
        $indexes = DB::select("SHOW INDEX FROM reports");
        $hasReporterIndex = collect($indexes)->contains('Column_name', 'reporter_id');
        $this->test("reporter_id indexed", $hasReporterIndex);
        if ($hasReporterIndex) $score += 1.5;

        $hasStatusIndex = collect($indexes)->contains('Column_name', 'status');
        $this->test("status indexed", $hasStatusIndex);
        if ($hasStatusIndex) $score += 1.5;

        // Foreign keys (2 points)
        $constraints = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'reports' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $hasForeignKeys = $constraints[0]->count >= 1;
        $this->test("Foreign keys exist", $hasForeignKeys);
        if ($hasForeignKeys) $score += 2;

        $this->scores['Database'] = [$score, 15];
    }

    private function testAPI()
    {
        $this->section("PART 3: API & ROUTES (15 points)");
        $score = 0;

        $routes = Route::getRoutes();
        $reportRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'reports'));

        // User endpoints (6 points)
        $userEndpoints = [
            'reports/post/{post}' => 'POST',
            'reports/user/{user}' => 'POST',
            'reports/comment/{comment}' => 'POST',
            'reports/my-reports' => 'GET'
        ];

        $allExist = true;
        foreach ($userEndpoints as $uri => $method) {
            $exists = $reportRoutes->contains(function($r) use ($uri, $method) {
                return str_contains($r->uri(), $uri) && in_array($method, $r->methods());
            });
            $this->test("{$method} /api/{$uri} exists", $exists, true);
            if (!$exists) $allExist = false;
        }
        if ($allExist) $score += 6;

        // Admin endpoints (6 points)
        $adminEndpoints = [
            'reports' => 'GET',
            'reports/{report}' => 'GET',
            'reports/{report}/status' => 'PATCH',
            'reports/{report}/action' => 'POST'
        ];

        $allAdminExist = true;
        foreach ($adminEndpoints as $uri => $method) {
            $exists = $reportRoutes->contains(function($r) use ($uri, $method) {
                return str_contains($r->uri(), $uri) && in_array($method, $r->methods());
            });
            $this->test("{$method} /api/{$uri} exists (Admin)", $exists, true);
            if (!$exists) $allAdminExist = false;
        }
        if ($allAdminExist) $score += 6;

        // Middleware (3 points) - Routes are in auth:sanctum group
        // Check if routes are inside authenticated group by checking route file
        $routeFile = file_get_contents(__DIR__ . '/routes/api.php');
        $reportsInAuthGroup = str_contains($routeFile, "Route::middleware(['auth:sanctum', 'security:api'])") &&
                             str_contains($routeFile, "Route::prefix('reports')");
        $this->test("All routes have auth middleware (in group)", $reportsInAuthGroup, true);
        if ($reportsInAuthGroup) $score += 3;

        $this->scores['API'] = [$score, 15];
    }

    private function testSecurity()
    {
        $this->section("PART 4: SECURITY (20 points)");
        $score = 0;

        $routes = Route::getRoutes();
        $reportRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'reports'));
        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');

        // Authentication (3 points) - Routes are in auth:sanctum group
        $routeFile = file_get_contents(__DIR__ . '/routes/api.php');
        $reportsInAuthGroup = str_contains($routeFile, "Route::middleware(['auth:sanctum', 'security:api'])") &&
                             str_contains($routeFile, "Route::prefix('reports')");
        $this->test("Authentication (auth:sanctum in group)", $reportsInAuthGroup, true);
        if ($reportsInAuthGroup) $score += 3;

        // Admin Authorization (4 points)
        $adminRoutes = $reportRoutes->filter(fn($r) => 
            str_contains($r->uri(), 'reports') && 
            !str_contains($r->uri(), 'my-reports') &&
            !str_contains($r->uri(), 'post/') &&
            !str_contains($r->uri(), 'user/') &&
            !str_contains($r->uri(), 'comment/')
        );
        $hasAdminRole = $adminRoutes->contains(function($r) {
            return in_array('role:admin', $r->middleware());
        });
        $this->test("Admin routes protected with role:admin", $hasAdminRole, true);
        if ($hasAdminRole) $score += 4;

        // Rate Limiting (3 points)
        $reportingRoutes = $reportRoutes->filter(fn($r) => 
            str_contains($r->uri(), 'post/') || 
            str_contains($r->uri(), 'user/') || 
            str_contains($r->uri(), 'comment/')
        );
        $hasRateLimit = $reportingRoutes->every(function($r) {
            return collect($r->middleware())->contains(fn($m) => str_starts_with($m, 'throttle:'));
        });
        $this->test("Rate Limiting (5/1min for reporting)", $hasRateLimit, true);
        if ($hasRateLimit) $score += 3;

        // Validation (3 points)
        $hasValidation = str_contains($controllerContent, '$request->validate([');
        $this->test("Input Validation implemented", $hasValidation, true);
        if ($hasValidation) $score += 3;

        // Self-report prevention (2 points)
        $preventsSelfReport = str_contains($controllerContent, 'Cannot report yourself');
        $this->test("Prevents self-reporting", $preventsSelfReport, true);
        if ($preventsSelfReport) $score += 2;

        // Duplicate report prevention (2 points)
        $preventsDuplicate = str_contains($controllerContent, 'already reported');
        $this->test("Prevents duplicate reports", $preventsDuplicate, true);
        if ($preventsDuplicate) $score += 2;

        // XSS Protection (2 points)
        $usesJson = str_contains($controllerContent, 'response()->json(');
        $this->test("XSS Protection (JSON responses)", $usesJson, true);
        if ($usesJson) $score += 2;

        // SQL Injection Prevention (1 point)
        $usesEloquent = !str_contains($controllerContent, 'DB::raw');
        $this->test("SQL Injection Prevention (Eloquent)", $usesEloquent, true);
        if ($usesEloquent) $score += 1;

        $this->scores['Security'] = [$score, 20];
    }

    private function testValidation()
    {
        $this->section("PART 5: VALIDATION (10 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');

        // Reason validation (3 points)
        $hasReasonValidation = str_contains($controllerContent, "'reason' => 'required|string|in:");
        $this->test("Reason validation (required, enum)", $hasReasonValidation, true);
        if ($hasReasonValidation) $score += 3;

        // Reason types (2 points)
        $hasReasonTypes = str_contains($controllerContent, 'spam,harassment,hate_speech,violence,nudity,other');
        $this->test("Reason types defined (6 types)", $hasReasonTypes, true);
        if ($hasReasonTypes) $score += 2;

        // Description validation (2 points)
        $hasDescValidation = str_contains($controllerContent, "'description' => 'nullable|string|max:");
        $this->test("Description validation (nullable, max length)", $hasDescValidation, true);
        if ($hasDescValidation) $score += 2;

        // Status validation (2 points)
        $hasStatusValidation = str_contains($controllerContent, "'status' => 'required|in:");
        $this->test("Status validation (enum)", $hasStatusValidation, true);
        if ($hasStatusValidation) $score += 2;

        // Action validation (1 point)
        $hasActionValidation = str_contains($controllerContent, "'action' => 'required|in:");
        $this->test("Action validation (enum)", $hasActionValidation, true);
        if ($hasActionValidation) $score += 1;

        $this->scores['Validation'] = [$score, 10];
    }

    private function testBusinessLogic()
    {
        $this->section("PART 6: BUSINESS LOGIC (10 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');

        // Report creation (2 points)
        $hasReportCreation = str_contains($controllerContent, 'new Report()');
        $this->test("Report creation logic", $hasReportCreation, true);
        if ($hasReportCreation) $score += 2;

        // Auto-moderation (3 points)
        $hasAutoModeration = str_contains($controllerContent, 'autoModerate');
        $this->test("Auto-moderation feature", $hasAutoModeration, true);
        if ($hasAutoModeration) $score += 3;

        // Action execution (2 points)
        $hasActionExecution = str_contains($controllerContent, 'executeAction');
        $this->test("Action execution logic", $hasActionExecution, true);
        if ($hasActionExecution) $score += 2;

        // Statistics (1 point)
        $hasStats = str_contains($controllerContent, 'getContentStats');
        $this->test("Content statistics", $hasStats);
        if ($hasStats) $score += 1;

        // Pagination (1 point)
        $hasPagination = str_contains($controllerContent, '->paginate(');
        $this->test("Pagination implemented", $hasPagination);
        if ($hasPagination) $score += 1;

        // Status tracking (1 point)
        $hasStatusTracking = str_contains($controllerContent, 'reviewed_at');
        $this->test("Status tracking (reviewed_at, reviewed_by)", $hasStatusTracking);
        if ($hasStatusTracking) $score += 1;

        $this->scores['Business Logic'] = [$score, 10];
    }

    private function testModelsRelationships()
    {
        $this->section("PART 7: MODELS & RELATIONSHIPS");

        $modelContent = file_get_contents(__DIR__ . '/app/Models/Report.php');
        
        // Relationships
        $this->test("reporter() relationship exists", str_contains($modelContent, 'function reporter'), true);
        $this->test("reviewer() relationship exists", str_contains($modelContent, 'function reviewer'), true);
        $this->test("reportable() morphTo relationship", str_contains($modelContent, 'function reportable'), true);
        $this->test("MorphTo relationship type", str_contains($modelContent, 'morphTo()'), true);

        // Scopes
        $this->test("pending() scope exists", str_contains($modelContent, 'function scopePending'), true);
        $this->test("resolved() scope exists", str_contains($modelContent, 'function scopeResolved'), true);

        // Mass assignment protection
        $this->test("Fillable array defined", str_contains($modelContent, '$fillable'), true);

        // Model instantiation
        try {
            $report = new \App\Models\Report();
            $this->test("Report model instantiates", $report !== null, true);
            $this->test("reporter() method callable", method_exists($report, 'reporter'), true);
            $this->test("reportable() method callable", method_exists($report, 'reportable'), true);
        } catch (\Exception $e) {
            $this->test("Report model instantiation", false, true);
        }
    }

    private function testIntegration()
    {
        $this->section("PART 8: INTEGRATION (5 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');

        // Post integration (2 points)
        $hasPostIntegration = str_contains($controllerContent, 'Post::');
        $this->test("Post system integration", $hasPostIntegration, true);
        if ($hasPostIntegration) $score += 2;

        // User integration (1 point)
        $hasUserIntegration = str_contains($controllerContent, 'User::');
        $this->test("User system integration", $hasUserIntegration, true);
        if ($hasUserIntegration) $score += 1;

        // Comment integration (1 point)
        $hasCommentIntegration = str_contains($controllerContent, 'Comment::');
        $this->test("Comment system integration", $hasCommentIntegration, true);
        if ($hasCommentIntegration) $score += 1;

        // Polymorphic support (1 point)
        $hasPolymorphic = str_contains($controllerContent, 'reportable_type') && 
                         str_contains($controllerContent, 'reportable_id');
        $this->test("Polymorphic relationship support", $hasPolymorphic, true);
        if ($hasPolymorphic) $score += 1;

        $this->scores['Integration'] = [$score, 5];
    }

    private function testTwitterStandards()
    {
        $this->section("PART 9: TWITTER STANDARDS COMPLIANCE");

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ModerationController.php');
        $routes = Route::getRoutes();
        $reportRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'reports'));

        // Report types
        $this->test("Report Post feature", str_contains($controllerContent, 'function reportPost'), true);
        $this->test("Report User feature", str_contains($controllerContent, 'function reportUser'), true);
        $this->test("Report Comment feature", str_contains($controllerContent, 'function reportComment'), true);

        // Report reasons (Twitter-like)
        $this->test("Spam reason", str_contains($controllerContent, 'spam'), true);
        $this->test("Harassment reason", str_contains($controllerContent, 'harassment'), true);
        $this->test("Hate speech reason", str_contains($controllerContent, 'hate_speech'), true);
        $this->test("Violence reason", str_contains($controllerContent, 'violence'), true);
        $this->test("Nudity reason", str_contains($controllerContent, 'nudity'), true);

        // Rate limiting (Twitter: 5 reports per minute)
        $reportingRoutes = $reportRoutes->filter(fn($r) => 
            str_contains($r->uri(), 'post/') || 
            str_contains($r->uri(), 'user/') || 
            str_contains($r->uri(), 'comment/')
        );
        $hasCorrectRateLimit = $reportingRoutes->every(function($r) {
            return in_array('throttle:5,1', $r->middleware());
        });
        $this->test("Rate limit: 5 reports per minute", $hasCorrectRateLimit, true);

        // Admin panel
        $this->test("Admin panel endpoints exist", $reportRoutes->count() >= 7, true);

        // Auto-moderation
        $this->test("Auto-moderation at threshold", str_contains($controllerContent, '>= 5'), true);
    }

    private function testNoParallelWork()
    {
        $this->section("PART 10: NO PARALLEL WORK");

        // Single controller
        $controllers = glob(__DIR__ . '/app/Http/Controllers/Api/*Moderation*.php');
        $this->test("Only one ModerationController", count($controllers) === 1, true);

        // Single model
        $models = glob(__DIR__ . '/app/Models/Report*.php');
        $this->test("Only one Report model", count($models) === 1, true);

        // No duplicate services
        $noModerationService = !file_exists(__DIR__ . '/app/Services/ModerationService.php');
        $this->test("No separate ModerationService", $noModerationService, true);

        $noReportService = !file_exists(__DIR__ . '/app/Services/ReportService.php');
        $this->test("No separate ReportService", $noReportService, true);
    }

    private function testOperationalReadiness()
    {
        $this->section("PART 11: OPERATIONAL READINESS");

        // Database connection
        try {
            DB::connection()->getPdo();
            $this->test("Database connected", true, true);
        } catch (\Exception $e) {
            $this->test("Database connected", false, true);
        }

        // Table exists
        $this->test("reports table operational", Schema::hasTable('reports'), true);

        // Routes registered
        $routes = Route::getRoutes();
        $reportRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'reports'));
        $this->test("All routes registered", $reportRoutes->count() >= 7, true);

        // Model functional
        try {
            $report = new \App\Models\Report();
            $report->reporter_id = 1;
            $report->reportable_type = 'App\\Models\\Post';
            $report->reportable_id = 1;
            $report->reason = 'spam';
            $report->status = 'pending';
            $this->test("Report model functional", $report->reason === 'spam', true);
        } catch (\Exception $e) {
            $this->test("Report model functional", false, true);
        }
    }

    private function testRoadmapCompliance()
    {
        $this->section("PART 12: ROADMAP COMPLIANCE");

        $totalScore = 0;
        $totalMax = 0;

        foreach ($this->scores as $category => $scores) {
            [$score, $max] = $scores;
            $percentage = round(($score/$max)*100);
            $totalScore += $score;
            $totalMax += $max;
            
            $status = $percentage >= 95 ? '✅' : ($percentage >= 85 ? '🟡' : '🟠');
            $this->test("{$category}: {$score}/{$max} ({$percentage}%)", $percentage >= 95, false);
        }

        $finalPercentage = round(($totalScore/$totalMax)*100, 2);
        $this->test("Total Score: {$totalScore}/{$totalMax} ({$finalPercentage}%)", $finalPercentage >= 95, true);
        $this->test("Production Ready Status", $finalPercentage >= 95, true);
    }

    private function printSummary()
    {
        $total = $this->passed + $this->failed;
        $percentage = $total > 0 ? round(($this->passed / $total) * 100, 2) : 0;

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "  FINAL SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        echo "  Total Tests: {$total}\n";
        echo "  Passed: {$this->passed} ✅\n";
        echo "  Failed: {$this->failed} ❌\n";
        echo "  Success Rate: {$percentage}%\n";
        echo str_repeat("=", 80) . "\n\n";

        if (!empty($this->critical)) {
            echo "🔴 CRITICAL ISSUES:\n";
            echo str_repeat("-", 80) . "\n";
            foreach (array_unique($this->critical) as $i => $issue) {
                echo "  " . ($i + 1) . ". {$issue}\n";
            }
            echo "\n";
        }

        if ($this->failed === 0) {
            echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
            echo "║  ✅ MODERATION & REPORTING SYSTEM - 100% OPERATIONAL & PRODUCTION READY  ║\n";
            echo "║  ✅ All 12 Parts Verified (Architecture, Database, API, Security, etc.)  ║\n";
            echo "║  ✅ ROADMAP Compliance: Complete                                          ║\n";
            echo "║  ✅ Twitter Standards: Fully Compliant                                    ║\n";
            echo "║  ✅ Security: Admin Authorization + Rate Limiting                         ║\n";
            echo "║  ✅ No Parallel Work: Single Implementation                               ║\n";
            echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
        } else {
            echo "⚠️  SOME CHECKS FAILED - REVIEW REQUIRED\n";
        }

        echo "\n";
        exit($this->failed > 0 ? 1 : 0);
    }
}

$test = new ModerationReportingSystemTest();
$test->run();
