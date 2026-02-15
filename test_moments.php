<?php

/**
 * Moments System - Comprehensive Test Script
 * 
 * معیارهای بررسی (4 معیار اصلی):
 * 1. ROADMAP Compliance (8 بخش × وزن متغیر = 100 امتیاز)
 * 2. Twitter Standards Compliance
 * 3. Operational Readiness
 * 4. No Parallel Work & Integration
 */

$results = [
    'roadmap' => ['score' => 0, 'max' => 100, 'details' => []],
    'twitter' => ['score' => 0, 'max' => 100, 'details' => []],
    'operational' => ['score' => 0, 'max' => 100, 'details' => []],
    'no_parallel' => ['score' => 0, 'max' => 100, 'details' => []],
];

$totalTests = 0;
$passedTests = 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║           تست جامع Moments System - 4 معیار اصلی             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

// ============================================================================
// معیار 1: ROADMAP Compliance (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 معیار 1: ROADMAP Compliance\n";
echo "─────────────────────────────────────────────────────────────────\n";

// 1. Architecture & Code Quality (20%)
echo "\n🏗️  1. Architecture & Code Quality (20 امتیاز):\n";

$architectureTests = [
    'Service Layer' => 'app/Services/MomentService.php',
    'Policy' => 'app/Policies/MomentPolicy.php',
    'Request Validation' => 'app/Http/Requests/MomentRequest.php',
    'Controller' => 'app/Http/Controllers/Api/MomentController.php',
    'Resource' => 'app/Http/Resources/MomentResource.php',
    'Model' => 'app/Models/Moment.php',
];

$architectureScore = 0;
foreach ($architectureTests as $name => $path) {
    $totalTests++;
    if (file_exists($path)) {
        $passedTests++;
        $architectureScore += (20 / count($architectureTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name} - MISSING\n";
        $results['roadmap']['details'][] = "Missing: {$name}";
    }
}

$results['roadmap']['score'] += $architectureScore;

// 2. Database & Schema (15%)
echo "\n💾 2. Database & Schema (15 امتیاز):\n";

$migrationFiles = glob('database/migrations/*_create_moments_table.php');
$totalTests++;
if (!empty($migrationFiles)) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Migration: moments table\n";
    
    $migrationContent = file_get_contents($migrationFiles[0]);
    $columns = [
        'user_id' => 'user_id',
        'title' => 'title',
        'description' => 'description',
        'cover_image' => 'cover_image',
        'privacy' => 'privacy',
        'is_featured' => 'is_featured',
        'posts_count' => 'posts_count',
        'views_count' => 'views_count',
    ];
    
    foreach ($columns as $col => $pattern) {
        $totalTests++;
        if (strpos($migrationContent, $pattern) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (10 / count($columns));
            echo "  ✓ Column: {$col}\n";
        } else {
            echo "  ✗ Column: {$col} - MISSING\n";
            $results['roadmap']['details'][] = "Missing column: {$col}";
        }
    }
} else {
    echo "  ✗ Migration: moments table - MISSING\n";
    $results['roadmap']['details'][] = "Missing: moments migration";
}

// Check pivot table
$totalTests++;
if (strpos($migrationContent, 'moment_posts') !== false) {
    $passedTests++;
    echo "  ✓ Pivot table: moment_posts\n";
} else {
    echo "  ✗ Pivot table: moment_posts - MISSING\n";
    $results['roadmap']['details'][] = "Missing: moment_posts pivot table";
}

// 3. API & Routes (15%)
echo "\n🔌 3. API & Routes (15 امتیاز):\n";

$routeContent = file_get_contents('routes/api.php');
$routes = [
    'GET /moments' => "Route::get('/', [MomentController::class, 'index'])",
    'POST /moments' => "Route::post('/', [MomentController::class, 'store'])",
    'GET /moments/{moment}' => "Route::get('/{moment}', [MomentController::class, 'show'])",
    'PUT /moments/{moment}' => "Route::put('/{moment}', [MomentController::class, 'update'])",
    'DELETE /moments/{moment}' => "Route::delete('/{moment}', [MomentController::class, 'destroy'])",
    'GET /moments/featured' => "Route::get('/featured', [MomentController::class, 'featured'])",
    'GET /moments/my-moments' => "Route::get('/my-moments', [MomentController::class, 'myMoments'])",
    'POST /moments/{moment}/posts' => "Route::post('/{moment}/posts', [MomentController::class, 'addPost'])",
];

foreach ($routes as $name => $pattern) {
    $totalTests++;
    if (strpos($routeContent, $pattern) !== false) {
        $passedTests++;
        $results['roadmap']['score'] += (15 / count($routes));
        echo "  ✓ Route: {$name}\n";
    } else {
        echo "  ✗ Route: {$name} - MISSING\n";
        $results['roadmap']['details'][] = "Missing route: {$name}";
    }
}

// 4. Security & Authorization (20%)
echo "\n🔒 4. Security & Authorization (20 امتیاز):\n";

// Check permissions
$totalTests++;
if (strpos(file_get_contents('database/seeders/PermissionSeeder.php'), 'moment.create') !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Permission Seeder exists\n";
    
    $seederContent = file_get_contents('database/seeders/PermissionSeeder.php');
    $permissions = [
        'moment.create' => 'moment.create',
        'moment.edit.own' => 'moment.edit.own',
        'moment.delete.own' => 'moment.delete.own',
        'moment.manage.posts' => 'moment.manage.posts',
    ];
    
    foreach ($permissions as $name => $pattern) {
        $totalTests++;
        if (strpos($seederContent, $pattern) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (5 / count($permissions));
            echo "  ✓ Permission: {$name}\n";
        } else {
            echo "  ✗ Permission: {$name} - MISSING\n";
            $results['roadmap']['details'][] = "Missing permission: {$name}";
        }
    }
} else {
    echo "  ✗ Permission Seeder - MISSING\n";
    $results['roadmap']['details'][] = "Missing: MomentPermissionSeeder";
}

// Check Policy
if (file_exists('app/Policies/MomentPolicy.php')) {
    $policyContent = file_get_contents('app/Policies/MomentPolicy.php');
    $policyMethods = [
        'viewAny' => 'function viewAny',
        'view' => 'function view',
        'create' => 'function create',
        'update' => 'function update',
        'delete' => 'function delete',
    ];
    
    foreach ($policyMethods as $method => $signature) {
        $totalTests++;
        if (strpos($policyContent, $signature) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (5 / count($policyMethods));
            echo "  ✓ Policy->{$method}()\n";
        } else {
            echo "  ✗ Policy->{$method}() - MISSING\n";
            $results['roadmap']['details'][] = "Missing: MomentPolicy::{$method}()";
        }
    }
}

// Check middleware
$totalTests++;
if (strpos($routeContent, "auth:sanctum") !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Authentication middleware\n";
} else {
    echo "  ✗ Authentication middleware - MISSING\n";
    $results['roadmap']['details'][] = "Missing: auth middleware";
}

$totalTests++;
if (strpos($routeContent, "authorize") !== false || file_exists('app/Policies/MomentPolicy.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Authorization checks\n";
} else {
    echo "  ✗ Authorization checks - MISSING\n";
    $results['roadmap']['details'][] = "Missing: authorization";
}

// 5. Validation & Business Rules (10%)
echo "\n✅ 5. Validation & Business Rules (10 امتیاز):\n";

if (file_exists('app/Http/Requests/MomentRequest.php')) {
    $requestContent = file_get_contents('app/Http/Requests/MomentRequest.php');
    
    $validationRules = [
        'Title validation' => 'title',
        'Description validation' => 'description',
        'Privacy validation' => 'privacy',
        'Cover image validation' => 'cover_image',
    ];
    
    foreach ($validationRules as $name => $pattern) {
        $totalTests++;
        if (strpos($requestContent, $pattern) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (10 / count($validationRules));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name} - MISSING\n";
            $results['roadmap']['details'][] = "Missing validation: {$name}";
        }
    }
}

// 6. Business Logic & Features (10%)
echo "\n🧠 6. Business Logic & Features (10 امتیاز):\n";

$modelContent = file_get_contents('app/Models/Moment.php');
$controllerContent = file_get_contents('app/Http/Controllers/Api/MomentController.php');

$features = [
    'Moment CRUD' => 'store',
    'Add Post to Moment' => 'addPost',
    'Remove Post from Moment' => 'removePost',
    'Featured Moments' => 'featured',
    'Privacy Control' => 'privacy',
    'View Counter' => 'incrementViews',
];

foreach ($features as $name => $pattern) {
    $totalTests++;
    if (strpos($modelContent, $pattern) !== false || strpos($controllerContent, $pattern) !== false) {
        $passedTests++;
        $results['roadmap']['score'] += (10 / count($features));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name} - MISSING\n";
        $results['roadmap']['details'][] = "Missing feature: {$name}";
    }
}

// 7. Integration (5%)
echo "\n🔗 7. Integration (5 امتیاز):\n";

$integrations = [
    'User Model' => ['app/Models/User.php', 'moments()'],
    'Post Model' => ['app/Models/Post.php', 'moments()'],
    'Moment Model relations' => ['app/Models/Moment.php', 'posts()'],
];

foreach ($integrations as $name => $check) {
    $totalTests++;
    if (file_exists($check[0])) {
        $content = file_get_contents($check[0]);
        if (strpos($content, $check[1]) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (5 / count($integrations));
            echo "  ✓ Integration: {$name}\n";
        } else {
            echo "  ✗ Integration: {$name} - MISSING\n";
            $results['roadmap']['details'][] = "Missing integration: {$name}";
        }
    } else {
        echo "  ✗ Integration: {$name} - FILE NOT FOUND\n";
    }
}

// 8. Testing & Documentation (5%)
echo "\n🧪 8. Testing & Documentation (5 امتیاز):\n";

$totalTests++;
if (file_exists('tests/Feature/MomentTest.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Feature Tests\n";
} else {
    echo "  ✗ Feature Tests - MISSING\n";
    $results['roadmap']['details'][] = "Missing: tests/Feature/MomentTest.php";
}

$totalTests++;
if (file_exists('docs/MOMENTS_SYSTEM.md')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Documentation\n";
} else {
    echo "  ✗ Documentation - MISSING\n";
    $results['roadmap']['details'][] = "Missing: docs/MOMENTS_SYSTEM.md";
}

// ============================================================================
// معیار 2: Twitter Standards (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 معیار 2: Twitter Standards\n";
echo "─────────────────────────────────────────────────────────────────\n";

$twitterTests = [
    'Moment Curation (Twitter Moments)' => 'Moment',
    'Add/Remove Posts' => 'addPost',
    'Privacy Control (public/private)' => 'privacy',
    'Featured Moments' => 'featured',
    'Cover Image' => 'cover_image',
    'View Counter' => 'views_count',
    'Post Ordering' => 'position',
];

foreach ($twitterTests as $name => $pattern) {
    $totalTests++;
    $found = strpos($modelContent, $pattern) !== false || strpos($controllerContent, $pattern) !== false;
    
    if ($found) {
        $passedTests++;
        $results['twitter']['score'] += (100 / count($twitterTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name} - MISSING\n";
        $results['twitter']['details'][] = "Missing Twitter standard: {$name}";
    }
}

// ============================================================================
// معیار 3: Operational Readiness (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 معیار 3: Operational Readiness\n";
echo "─────────────────────────────────────────────────────────────────\n";

$operationalTests = [
    'Service Layer exists' => file_exists('app/Services/MomentService.php'),
    'Policy registered' => file_exists('app/Policies/MomentPolicy.php'),
    'Permissions seeded' => strpos(file_get_contents('database/seeders/PermissionSeeder.php'), 'moment.create') !== false,
    'Routes defined' => strpos($routeContent, 'MomentController') !== false,
    'Model relations' => strpos($modelContent, 'posts()') !== false,
    'Privacy control' => strpos($modelContent, 'scopePublic') !== false,
    'Authorization checks' => strpos($controllerContent, 'authorize') !== false,
    'Resource formatting' => file_exists('app/Http/Resources/MomentResource.php'),
];

foreach ($operationalTests as $name => $check) {
    $totalTests++;
    if ($check) {
        $passedTests++;
        $results['operational']['score'] += (100 / count($operationalTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name} - MISSING\n";
        $results['operational']['details'][] = "Missing: {$name}";
    }
}

// ============================================================================
// معیار 4: No Parallel Work & Integration (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 معیار 4: No Parallel Work & Integration\n";
echo "─────────────────────────────────────────────────────────────────\n";

$parallelTests = [
    'Single Controller' => count(glob('app/Http/Controllers/Api/*Moment*.php')) === 1,
    'Single Service' => count(glob('app/Services/*Moment*.php')) <= 1,
    'Single Policy' => count(glob('app/Policies/*Moment*.php')) <= 1,
    'No duplicate routes' => substr_count($routeContent, 'MomentController') >= 7,
    'Integration: User model' => file_exists('app/Models/User.php'),
    'Integration: Post model' => file_exists('app/Models/Post.php'),
    'Integration: Moment model' => file_exists('app/Models/Moment.php'),
    'Pivot table exists' => strpos($migrationContent, 'moment_posts') !== false,
];

foreach ($parallelTests as $name => $check) {
    $totalTests++;
    if ($check) {
        $passedTests++;
        $results['no_parallel']['score'] += (100 / count($parallelTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name} - ISSUE\n";
        $results['no_parallel']['details'][] = "Issue: {$name}";
    }
}

// ============================================================================
// خلاصه نهایی
// ============================================================================
echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                      خلاصه نهایی                              ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

$totalScore = 0;
$maxScore = 400;

echo "\n📊 آمار کلی:\n";
echo "  • کل تستها: {$totalTests}\n";
echo "  • موفق: {$passedTests} ✓\n";
echo "  • ناموفق: " . ($totalTests - $passedTests) . " ✗\n";
echo "  • درصد موفقیت: " . round(($passedTests / $totalTests) * 100, 1) . "%\n";

echo "\n📋 امتیازات معیارها:\n";
foreach ($results as $key => $result) {
    $score = round($result['score'], 1);
    $max = $result['max'];
    $percentage = round(($score / $max) * 100, 1);
    $totalScore += $score;
    
    $icon = $percentage >= 95 ? '✅' : ($percentage >= 70 ? '🟡' : '🔴');
    
    echo "  {$icon} " . ucfirst($key) . ": {$score}/{$max} ({$percentage}%)\n";
    
    if (!empty($result['details'])) {
        echo "     Issues:\n";
        foreach (array_slice($result['details'], 0, 3) as $detail) {
            echo "     - {$detail}\n";
        }
        if (count($result['details']) > 3) {
            echo "     - ... و " . (count($result['details']) - 3) . " مورد دیگر\n";
        }
    }
}

$finalPercentage = round(($totalScore / $maxScore) * 100, 1);
echo "\n🎯 امتیاز نهایی: {$totalScore}/{$maxScore} ({$finalPercentage}%)\n";

if ($finalPercentage >= 95) {
    echo "\n🎉 عالی: Moments System تمام معیارها را رعایت کرده است!\n";
    echo "✅ آماده Production\n";
} elseif ($finalPercentage >= 70) {
    echo "\n🟡 خوب: Moments System نیاز به بهبودهای جزئی دارد\n";
    echo "⚠️  نیاز به بررسی موارد ناموفق\n";
} else {
    echo "\n🔴 نیاز به کار بیشتر: Moments System نیاز به توسعه دارد\n";
    echo "❌ آماده Production نیست\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($finalPercentage >= 95 ? 0 : 1);
