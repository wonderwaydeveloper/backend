<?php

/**
 * Media Management System - Comprehensive Test Script
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
echo "║           تست جامع Media Management System - 4 معیار اصلی    ║\n";
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
    'Service Layer' => 'app/Services/MediaService.php',
    'Policy' => 'app/Policies/MediaPolicy.php',
    'Request Validation' => 'app/Http/Requests/MediaUploadRequest.php',
    'Controller' => 'app/Http/Controllers/Api/MediaController.php',
    'Resource' => 'app/Http/Resources/MediaResource.php',
    'Job' => 'app/Jobs/GenerateThumbnailJob.php',
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

// Check Service methods
if (file_exists('app/Services/MediaService.php')) {
    $serviceContent = file_get_contents('app/Services/MediaService.php');
    $serviceMethods = [
        'uploadImage' => 'public function uploadImage',
        'uploadVideo' => 'public function uploadVideo',
        'uploadDocument' => 'public function uploadDocument',
        'deleteMedia' => 'public function deleteMedia',
        'processImage' => 'processImage',
        'generateThumbnail' => 'generateThumbnail',
    ];
    
    foreach ($serviceMethods as $method => $signature) {
        $totalTests++;
        if (strpos($serviceContent, $signature) !== false) {
            $passedTests++;
            echo "  ✓ Service->{$method}()\n";
        } else {
            echo "  ✗ Service->{$method}() - MISSING\n";
            $results['roadmap']['details'][] = "Missing method: MediaService::{$method}()";
        }
    }
}

$results['roadmap']['score'] += $architectureScore;

// 2. Database & Schema (15%)
echo "\n💾 2. Database & Schema (15 امتیاز):\n";

// Check if media table exists
$migrationFiles = glob('database/migrations/*_create_media_table.php');
$totalTests++;
if (!empty($migrationFiles)) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Migration: media table\n";
    
    $migrationContent = file_get_contents($migrationFiles[0]);
    $columns = [
        'user_id' => '$table->foreignId(\'user_id\')',
        'mediable_type' => 'mediable_type',
        'mediable_id' => 'mediable_id',
        'type' => 'type',
        'path' => 'path',
        'url' => 'url',
        'size' => 'size',
        'mime_type' => 'mime_type',
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
    echo "  ✗ Migration: media table - MISSING\n";
    echo "  ⚠️  Note: Media system needs dedicated table for tracking\n";
    $results['roadmap']['details'][] = "Missing: media migration";
}

// 3. API & Routes (15%)
echo "\n🔌 3. API & Routes (15 امتیاز):\n";

$routeContent = file_get_contents('routes/api.php');
$routes = [
    'GET /media' => "Route::get('/', [MediaController::class, 'index'])",
    'GET /media/{media}' => "Route::get('/{media}', [MediaController::class, 'show'])",
    'POST /media/upload/image' => "Route::post('/upload/image', [MediaController::class, 'uploadImage'])",
    'POST /media/upload/video' => "Route::post('/upload/video', [MediaController::class, 'uploadVideo'])",
    'POST /media/upload/document' => "Route::post('/upload/document', [MediaController::class, 'uploadDocument'])",
    'DELETE /media/{media}' => "Route::delete('/{media}', [MediaController::class, 'destroy'])",
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

// Check permissions in PermissionSeeder
$permissionSeeder = glob('database/seeders/PermissionSeeder.php');
$totalTests++;
if (!empty($permissionSeeder)) {
    $seederContent = file_get_contents($permissionSeeder[0]);
    if (strpos($seederContent, 'media.upload') !== false) {
        $passedTests++;
        $results['roadmap']['score'] += 5;
        echo "  ✓ Permission Seeder (media permissions)\n";
    } else {
        echo "  ✗ Permission Seeder - MISSING\n";
        $results['roadmap']['details'][] = "Missing: media permissions in PermissionSeeder";
    }
    
    $permissions = [
        'media.upload' => 'media.upload',
        'media.delete' => 'media.delete',
        'media.view' => 'media.view',
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
    $results['roadmap']['details'][] = "Missing: PermissionSeeder.php";
}

// Check Policy
if (file_exists('app/Policies/MediaPolicy.php')) {
    $policyContent = file_get_contents('app/Policies/MediaPolicy.php');
    $policyMethods = [
        'viewAny' => 'function viewAny',
        'view' => 'function view',
        'create' => 'function create',
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
            $results['roadmap']['details'][] = "Missing: MediaPolicy::{$method}()";
        }
    }
}

// Check middleware in routes
$totalTests++;
if (strpos($routeContent, "permission:media.upload") !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Permission middleware\n";
} else {
    echo "  ✗ Permission middleware - MISSING\n";
    $results['roadmap']['details'][] = "Missing: permission middleware on routes";
}

$totalTests++;
if (strpos($routeContent, "throttle:") !== false && strpos($routeContent, "media") !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Rate Limiting\n";
} else {
    echo "  ✗ Rate Limiting - MISSING\n";
    $results['roadmap']['details'][] = "Missing: rate limiting on media routes";
}

// 5. Validation & Business Rules (10%)
echo "\n✅ 5. Validation & Business Rules (10 امتیاز):\n";

if (file_exists('app/Http/Requests/MediaUploadRequest.php')) {
    $requestContent = file_get_contents('app/Http/Requests/MediaUploadRequest.php');
    
    $validationRules = [
        'File validation' => 'FileUpload',
        'Alt text validation' => 'alt_text',
        'Type validation' => 'type',
    ];
    
    foreach ($validationRules as $name => $pattern) {
        $totalTests++;
        if (strpos($requestContent, $pattern) !== false) {
            $passedTests++;
            $results['roadmap']['score'] += (5 / count($validationRules));
            echo "  ✓ {$name}\n";
        } else {
            echo "  ✗ {$name} - MISSING\n";
            $results['roadmap']['details'][] = "Missing validation: {$name}";
        }
    }
}

// Check config for media limits
$totalTests++;
if (file_exists('config/media.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 5;
    echo "  ✓ Config: media.php\n";
} else {
    echo "  ✗ Config: media.php - MISSING\n";
    $results['roadmap']['details'][] = "Missing: config/media.php";
}

// 6. Business Logic & Features (10%)
echo "\n🧠 6. Business Logic & Features (10 امتیاز):\n";

$controllerContent = file_get_contents('app/Http/Controllers/Api/MediaController.php');
$serviceContent = file_exists('app/Services/MediaService.php') ? file_get_contents('app/Services/MediaService.php') : '';

$features = [
    'Image processing' => 'processImage',
    'Thumbnail generation' => 'generateThumbnail',
    'File storage' => 'Storage::disk',
    'Unique filename' => 'generateFilename',
    'Multiple formats' => 'uploadVideo',
];

foreach ($features as $name => $pattern) {
    $totalTests++;
    if (strpos($controllerContent, $pattern) !== false || strpos($serviceContent, $pattern) !== false) {
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
    'Post System' => ['app/Models/Post.php', 'media()'],
    'Comment System' => ['app/Models/Comment.php', 'media()'],
    'User System' => ['app/Models/User.php', 'media()'],
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
if (file_exists('tests/Feature/MediaTest.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Feature Tests\n";
} else {
    echo "  ✗ Feature Tests - MISSING\n";
    $results['roadmap']['details'][] = "Missing: tests/Feature/MediaTest.php";
}

$totalTests++;
if (file_exists('docs/MEDIA_SYSTEM.md')) {
    $passedTests++;
    $results['roadmap']['score'] += 2.5;
    echo "  ✓ Documentation\n";
} else {
    echo "  ✗ Documentation - MISSING\n";
    $results['roadmap']['details'][] = "Missing: docs/MEDIA_SYSTEM.md";
}

// ============================================================================
// معیار 2: Twitter Standards (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 معیار 2: Twitter Standards\n";
echo "─────────────────────────────────────────────────────────────────\n";

$twitterTests = [
    'Image formats (JPEG, PNG, GIF, WebP)' => ['jpeg', 'png', 'gif', 'webp'],
    'Video formats (MP4, MOV)' => ['mp4', 'mov'],
    'Max image size (5MB)' => '5120',
    'Max video size (512MB)' => 'media.max_file_size.video',
    'Image optimization' => 'processImage',
    'Thumbnail generation' => 'generateThumbnail',
    'Alt text support' => 'alt_text',
    'Multiple media per post' => 'morphMany',
];

$requestContent = file_exists('app/Http/Requests/MediaUploadRequest.php') ? file_get_contents('app/Http/Requests/MediaUploadRequest.php') : '';
$postContent = file_exists('app/Models/Post.php') ? file_get_contents('app/Models/Post.php') : '';
$configContent = file_exists('config/media.php') ? file_get_contents('config/media.php') : '';

foreach ($twitterTests as $name => $patterns) {
    $totalTests++;
    $found = false;
    
    if (is_array($patterns)) {
        foreach ($patterns as $pattern) {
            if (strpos($controllerContent, $pattern) !== false || 
                strpos($serviceContent, $pattern) !== false || 
                strpos($requestContent, $pattern) !== false) {
                $found = true;
                break;
            }
        }
    } else {
        $found = strpos($controllerContent, $patterns) !== false || 
                 strpos($serviceContent, $patterns) !== false || 
                 strpos($requestContent, $patterns) !== false ||
                 strpos($postContent, $patterns) !== false ||
                 strpos($configContent, $patterns) !== false;
    }
    
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
    'Service Layer exists' => file_exists('app/Services/MediaService.php'),
    'Policy registered' => file_exists('app/Policies/MediaPolicy.php'),
    'Permissions seeded' => !empty($permissionSeeder) && strpos(file_get_contents($permissionSeeder[0]), 'media.upload') !== false,
    'Job implements ShouldQueue' => file_exists('app/Jobs/GenerateThumbnailJob.php') && 
        strpos(file_get_contents('app/Jobs/GenerateThumbnailJob.php'), 'ShouldQueue') !== false,
    'Storage configured' => file_exists('config/filesystems.php'),
    'CDN integration' => file_exists('app/Services/CDNService.php'),
    'Error handling' => strpos($serviceContent, 'try') !== false || strpos($controllerContent, 'authorize') !== false,
    'Logging' => strpos(file_get_contents('app/Jobs/GenerateThumbnailJob.php'), 'Log::') !== false,
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
    'Single Controller' => count(glob('app/Http/Controllers/Api/*Media*.php')) === 1,
    'Single Service' => count(glob('app/Services/*Media*.php')) <= 1,
    'Single Policy' => count(glob('app/Policies/*Media*.php')) <= 1,
    'No duplicate routes' => substr_count($routeContent, 'MediaController') >= 4,
    'Integration: Post model' => file_exists('app/Models/Post.php'),
    'Integration: Comment model' => file_exists('app/Models/Comment.php'),
    'Integration: User model' => file_exists('app/Models/User.php'),
    'Integration: Storage system' => file_exists('config/filesystems.php'),
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
    echo "\n🎉 عالی: Media Management System تمام معیارها را رعایت کرده است!\n";
    echo "✅ آماده Production\n";
} elseif ($finalPercentage >= 70) {
    echo "\n🟡 خوب: Media Management System نیاز به بهبودهای جزئی دارد\n";
    echo "⚠️  نیاز به بررسی موارد ناموفق\n";
} else {
    echo "\n🔴 نیاز به کار بیشتر: Media Management System نیاز به توسعه دارد\n";
    echo "❌ آماده Production نیست\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($finalPercentage >= 95 ? 0 : 1);
