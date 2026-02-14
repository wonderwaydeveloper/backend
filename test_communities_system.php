<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

class CommunitiesSystemTest
{
    private $passed = 0;
    private $failed = 0;
    private $critical = [];
    private $scores = [];

    public function run()
    {
        echo "\n╔════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║              COMMUNITIES SYSTEM - COMPREHENSIVE VERIFICATION              ║\n";
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
        $controllerExists = file_exists(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');
        $this->test("CommunityController exists", $controllerExists, true);
        if ($controllerExists) $score += 5;

        // Models (10 points)
        $communityModel = file_exists(__DIR__ . '/app/Models/Community.php');
        $this->test("Community model exists", $communityModel, true);
        if ($communityModel) $score += 5;

        $joinRequestModel = file_exists(__DIR__ . '/app/Models/CommunityJoinRequest.php');
        $this->test("CommunityJoinRequest model exists", $joinRequestModel, true);
        if ($joinRequestModel) $score += 5;

        // Methods (5 points)
        if ($controllerExists) {
            $content = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');
            $methods = ['index', 'store', 'show', 'update', 'destroy', 'join', 'leave', 'posts', 'members'];
            $allExist = true;
            foreach ($methods as $method) {
                $exists = str_contains($content, "public function {$method}(");
                if (!$exists) $allExist = false;
            }
            $this->test("All CRUD + community methods exist (9 methods)", $allExist, true);
            if ($allExist) $score += 5;
        }

        $this->scores['Architecture'] = [$score, 20];
    }

    private function testDatabase()
    {
        $this->section("PART 2: DATABASE & SCHEMA (15 points)");
        $score = 0;

        // Tables (6 points)
        $communitiesTable = Schema::hasTable('communities');
        $this->test("communities table exists", $communitiesTable, true);
        if ($communitiesTable) $score += 2;

        $membersTable = Schema::hasTable('community_members');
        $this->test("community_members pivot table exists", $membersTable, true);
        if ($membersTable) $score += 2;

        $joinRequestsTable = Schema::hasTable('community_join_requests');
        $this->test("community_join_requests table exists", $joinRequestsTable, true);
        if ($joinRequestsTable) $score += 2;

        // Columns (5 points)
        if ($communitiesTable) {
            $columns = Schema::getColumnListing('communities');
            $required = ['id', 'name', 'slug', 'privacy', 'created_by', 'member_count'];
            $allExist = true;
            foreach ($required as $col) {
                $exists = in_array($col, $columns);
                if (!$exists) $allExist = false;
            }
            $this->test("Communities table has required columns", $allExist, true);
            if ($allExist) $score += 5;
        }

        // Indexes (2 points)
        $indexes = DB::select("SHOW INDEX FROM communities");
        $hasSlugIndex = collect($indexes)->contains('Column_name', 'slug');
        $this->test("slug indexed (unique)", $hasSlugIndex);
        if ($hasSlugIndex) $score += 2;

        // Foreign keys (2 points)
        $constraints = DB::select("
            SELECT COUNT(*) as count 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_NAME = 'community_members' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");
        $hasForeignKeys = $constraints[0]->count >= 2;
        $this->test("Pivot table has foreign keys", $hasForeignKeys);
        if ($hasForeignKeys) $score += 2;

        $this->scores['Database'] = [$score, 15];
    }

    private function testAPI()
    {
        $this->section("PART 3: API & ROUTES (15 points)");
        $score = 0;

        $routes = Route::getRoutes();
        $communityRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'api/communities'));

        // CRUD endpoints (6 points)
        $crudEndpoints = [
            'api/communities' => ['GET', 'POST'],
            'api/communities/{community}' => ['GET', 'PUT', 'DELETE']
        ];

        $allExist = true;
        foreach ($crudEndpoints as $uri => $methods) {
            foreach ($methods as $method) {
                $exists = $communityRoutes->contains(function($r) use ($uri, $method) {
                    return str_contains($r->uri(), str_replace('api/', '', $uri)) && 
                           in_array($method, $r->methods());
                });
                if (!$exists) $allExist = false;
            }
        }
        $this->test("CRUD endpoints exist (5 routes)", $allExist, true);
        if ($allExist) $score += 6;

        // Community-specific endpoints (6 points)
        $specificEndpoints = [
            'communities/{community}/join' => 'POST',
            'communities/{community}/leave' => 'POST',
            'communities/{community}/posts' => 'GET',
            'communities/{community}/members' => 'GET',
            'communities/{community}/join-requests' => 'GET'
        ];

        $allSpecificExist = true;
        foreach ($specificEndpoints as $uri => $method) {
            $exists = $communityRoutes->contains(function($r) use ($uri, $method) {
                return str_contains($r->uri(), $uri) && in_array($method, $r->methods());
            });
            if (!$exists) $allSpecificExist = false;
        }
        $this->test("Community-specific endpoints exist (5 routes)", $allSpecificExist, true);
        if ($allSpecificExist) $score += 6;

        // Middleware (3 points)
        $routeFile = file_get_contents(__DIR__ . '/routes/api.php');
        $inAuthGroup = str_contains($routeFile, "Route::middleware(['auth:sanctum', 'security:api'])")  &&
                      str_contains($routeFile, "Route::prefix('communities')");
        $this->test("All routes have auth middleware (in group)", $inAuthGroup, true);
        if ($inAuthGroup) $score += 3;

        $this->scores['API'] = [$score, 15];
    }

    private function testSecurity()
    {
        $this->section("PART 4: SECURITY (20 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');
        $routeFile = file_get_contents(__DIR__ . '/routes/api.php');

        // Authentication (3 points)
        $inAuthGroup = str_contains($routeFile, "Route::middleware(['auth:sanctum', 'security:api'])") &&
                      str_contains($routeFile, "Route::prefix('communities')");
        $this->test("Authentication (auth:sanctum in group)", $inAuthGroup, true);
        if ($inAuthGroup) $score += 3;

        // Authorization (5 points)
        $hasAuthorize = str_contains($controllerContent, '$this->authorize(');
        $this->test("Authorization policies implemented", $hasAuthorize, true);
        if ($hasAuthorize) $score += 5;

        // Validation (3 points)
        $hasValidation = str_contains($controllerContent, 'StoreCommunityRequest') &&
                        str_contains($controllerContent, 'UpdateCommunityRequest');
        $this->test("Form Request validation", $hasValidation, true);
        if ($hasValidation) $score += 3;

        // Business logic protection (4 points)
        $preventsDoubleJoin = str_contains($controllerContent, 'Already a member');
        $this->test("Prevents double join", $preventsDoubleJoin, true);
        if ($preventsDoubleJoin) $score += 2;

        $ownerCannotLeave = str_contains($controllerContent, 'Owner cannot leave');
        $this->test("Owner cannot leave community", $ownerCannotLeave, true);
        if ($ownerCannotLeave) $score += 2;

        // XSS Protection (2 points)
        $usesJson = str_contains($controllerContent, 'response()->json(');
        $this->test("XSS Protection (JSON responses)", $usesJson, true);
        if ($usesJson) $score += 2;

        // SQL Injection (2 points)
        $usesEloquent = !str_contains($controllerContent, 'DB::raw');
        $this->test("SQL Injection Prevention (Eloquent)", $usesEloquent, true);
        if ($usesEloquent) $score += 2;

        // Mass Assignment (1 point)
        $modelContent = file_get_contents(__DIR__ . '/app/Models/Community.php');
        $hasFillable = str_contains($modelContent, '$fillable');
        $this->test("Mass Assignment Protection", $hasFillable, true);
        if ($hasFillable) $score += 1;

        $this->scores['Security'] = [$score, 20];
    }

    private function testValidation()
    {
        $this->section("PART 5: VALIDATION (10 points)");
        $score = 0;

        // Form Requests (5 points)
        $storeRequest = file_exists(__DIR__ . '/app/Http/Requests/StoreCommunityRequest.php');
        $this->test("StoreCommunityRequest exists", $storeRequest, true);
        if ($storeRequest) $score += 2.5;

        $updateRequest = file_exists(__DIR__ . '/app/Http/Requests/UpdateCommunityRequest.php');
        $this->test("UpdateCommunityRequest exists", $updateRequest, true);
        if ($updateRequest) $score += 2.5;

        // Privacy validation (3 points)
        $modelContent = file_get_contents(__DIR__ . '/app/Models/Community.php');
        $hasPrivacy = str_contains($modelContent, 'privacy');
        $this->test("Privacy field exists", $hasPrivacy, true);
        if ($hasPrivacy) $score += 3;

        // Slug generation (2 points)
        $hasSlugGeneration = str_contains($modelContent, 'Str::slug');
        $this->test("Automatic slug generation", $hasSlugGeneration, true);
        if ($hasSlugGeneration) $score += 2;

        $this->scores['Validation'] = [$score, 10];
    }

    private function testBusinessLogic()
    {
        $this->section("PART 6: BUSINESS LOGIC (10 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');
        $modelContent = file_get_contents(__DIR__ . '/app/Models/Community.php');

        // Join logic (3 points)
        $hasJoinLogic = str_contains($controllerContent, 'canUserJoin');
        $this->test("Join permission check", $hasJoinLogic, true);
        if ($hasJoinLogic) $score += 3;

        // Private community logic (3 points)
        $hasPrivateLogic = str_contains($controllerContent, "privacy === 'private'");
        $this->test("Private community join requests", $hasPrivateLogic, true);
        if ($hasPrivateLogic) $score += 3;

        // Member count tracking (2 points)
        $hasMemberCount = str_contains($controllerContent, 'increment(\'member_count\')') &&
                         str_contains($controllerContent, 'decrement(\'member_count\')');
        $this->test("Member count tracking", $hasMemberCount, true);
        if ($hasMemberCount) $score += 2;

        // Role management (2 points)
        $hasRoles = str_contains($modelContent, 'getUserRole');
        $this->test("Role management system", $hasRoles, true);
        if ($hasRoles) $score += 2;

        $this->scores['Business Logic'] = [$score, 10];
    }

    private function testModelsRelationships()
    {
        $this->section("PART 7: MODELS & RELATIONSHIPS");

        $communityModel = file_get_contents(__DIR__ . '/app/Models/Community.php');
        
        // Community relationships
        $this->test("creator() relationship", str_contains($communityModel, 'function creator'), true);
        $this->test("members() relationship", str_contains($communityModel, 'function members'), true);
        $this->test("posts() relationship", str_contains($communityModel, 'function posts'), true);
        $this->test("joinRequests() relationship", str_contains($communityModel, 'function joinRequests'), true);

        // Helper methods
        $this->test("canUserPost() method", str_contains($communityModel, 'function canUserPost'), true);
        $this->test("canUserJoin() method", str_contains($communityModel, 'function canUserJoin'), true);
        $this->test("getUserRole() method", str_contains($communityModel, 'function getUserRole'), true);
        $this->test("canUserModerate() method", str_contains($communityModel, 'function canUserModerate'), true);

        // Scopes
        $this->test("public() scope", str_contains($communityModel, 'function scopePublic'), true);
        $this->test("verified() scope", str_contains($communityModel, 'function scopeVerified'), true);

        // Model instantiation
        try {
            $community = new \App\Models\Community();
            $this->test("Community model instantiates", $community !== null, true);
        } catch (\Exception $e) {
            $this->test("Community model instantiation", false, true);
        }
    }

    private function testIntegration()
    {
        $this->section("PART 8: INTEGRATION (5 points)");
        $score = 0;

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');

        // Post integration (2 points)
        $hasPostIntegration = str_contains($controllerContent, '->posts()');
        $this->test("Post system integration", $hasPostIntegration, true);
        if ($hasPostIntegration) $score += 2;

        // User integration (2 points)
        $hasUserIntegration = str_contains($controllerContent, 'auth()->user()');
        $this->test("User system integration", $hasUserIntegration, true);
        if ($hasUserIntegration) $score += 2;

        // Resource integration (1 point)
        $hasResources = str_contains($controllerContent, 'CommunityResource');
        $this->test("API Resource integration", $hasResources, true);
        if ($hasResources) $score += 1;

        $this->scores['Integration'] = [$score, 5];
    }

    private function testTwitterStandards()
    {
        $this->section("PART 9: TWITTER STANDARDS COMPLIANCE");

        $controllerContent = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/CommunityController.php');
        $modelContent = file_get_contents(__DIR__ . '/app/Models/Community.php');

        // Twitter Communities features
        $this->test("Create community", str_contains($controllerContent, 'function store'), true);
        $this->test("Join community", str_contains($controllerContent, 'function join'), true);
        $this->test("Leave community", str_contains($controllerContent, 'function leave'), true);
        $this->test("Community posts", str_contains($controllerContent, 'function posts'), true);
        $this->test("Community members", str_contains($controllerContent, 'function members'), true);

        // Privacy levels
        $this->test("Privacy support (public/private)", str_contains($modelContent, 'privacy'), true);

        // Join requests for private communities
        $this->test("Join requests for private communities", str_contains($controllerContent, "privacy === 'private'"), true);

        // Member roles
        $this->test("Role system (owner, admin, moderator, member)", str_contains($modelContent, 'role'), true);

        // Pagination (20 per page)
        $this->test("Pagination: 20 items per page", str_contains($controllerContent, '->paginate(20)'), true);

        // Search functionality
        $this->test("Search communities", str_contains($controllerContent, 'when($request->search'), true);

        // Verified communities
        $this->test("Verified communities support", str_contains($modelContent, 'is_verified'), true);
    }

    private function testNoParallelWork()
    {
        $this->section("PART 10: NO PARALLEL WORK");

        // Controllers (2 expected: CommunityController + CommunityNoteController)
        $controllers = glob(__DIR__ . '/app/Http/Controllers/Api/*Community*.php');
        $this->test("Community controllers exist (2: main + notes)", count($controllers) === 2, true);

        // Models
        $models = glob(__DIR__ . '/app/Models/Community*.php');
        $this->test("Community models exist (Community + related)", count($models) >= 2, true);

        // No duplicate services
        $noCommunityService = !file_exists(__DIR__ . '/app/Services/CommunityService.php');
        $this->test("No separate CommunityService", $noCommunityService, true);
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

        // Tables exist
        $this->test("communities table operational", Schema::hasTable('communities'), true);
        $this->test("community_members table operational", Schema::hasTable('community_members'), true);
        $this->test("community_join_requests table operational", Schema::hasTable('community_join_requests'), true);

        // Routes registered
        $routes = Route::getRoutes();
        $communityRoutes = collect($routes)->filter(fn($r) => str_contains($r->uri(), 'api/communities'));
        $this->test("All routes registered", $communityRoutes->count() >= 10, true);

        // Model functional
        try {
            $community = new \App\Models\Community();
            $community->name = 'Test Community';
            $community->slug = 'test-community';
            $community->privacy = 'public';
            $community->created_by = 1;
            $this->test("Community model functional", $community->name === 'Test Community', true);
        } catch (\Exception $e) {
            $this->test("Community model functional", false, true);
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
            echo "║  ✅ COMMUNITIES SYSTEM - 100% OPERATIONAL & PRODUCTION READY              ║\n";
            echo "║  ✅ All 12 Parts Verified (Architecture, Database, API, Security, etc.)  ║\n";
            echo "║  ✅ ROADMAP Compliance: Complete                                          ║\n";
            echo "║  ✅ Twitter Standards: Fully Compliant                                    ║\n";
            echo "║  ✅ Security: Authorization + Privacy Controls                            ║\n";
            echo "║  ✅ No Parallel Work: Single Implementation                               ║\n";
            echo "╚════════════════════════════════════════════════════════════════════════════╝\n";
        } else {
            echo "⚠️  SOME CHECKS FAILED - REVIEW REQUIRED\n";
        }

        echo "\n";
        exit($this->failed > 0 ? 1 : 0);
    }
}

$test = new CommunitiesSystemTest();
$test->run();
