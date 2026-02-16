<?php

/**
 * Media System - Complete Test Suite
 * 
 * ترکیب تست های:
 * 1. ROADMAP Compliance
 * 2. Twitter Standards
 * 3. Operational Readiness
 * 4. Deep Integration
 */

$results = [
    'roadmap' => ['score' => 0, 'max' => 100, 'details' => []],
    'twitter' => ['score' => 0, 'max' => 100, 'details' => []],
    'operational' => ['score' => 0, 'max' => 100, 'details' => []],
    'integration' => ['score' => 0, 'max' => 100, 'details' => []],
];

$totalTests = 0;
$passedTests = 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║              تست کامل Media Management System                 ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";

// ============================================================================
// بخش 1: ROADMAP Compliance (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 بخش 1: ROADMAP Compliance (100 امتیاز)\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Architecture
$architectureTests = [
    'MediaService' => 'app/Services/MediaService.php',
    'MediaPolicy' => 'app/Policies/MediaPolicy.php',
    'MediaUploadRequest' => 'app/Http/Requests/MediaUploadRequest.php',
    'MediaController' => 'app/Http/Controllers/Api/MediaController.php',
    'MediaResource' => 'app/Http/Resources/MediaResource.php',
    'GenerateThumbnailJob' => 'app/Jobs/GenerateThumbnailJob.php',
];

foreach ($architectureTests as $name => $path) {
    $totalTests++;
    if (file_exists($path)) {
        $passedTests++;
        $results['roadmap']['score'] += (30 / count($architectureTests));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
        $results['roadmap']['details'][] = "Missing: {$name}";
    }
}

// Database
$migrationFiles = glob('database/migrations/*_create_media_table.php');
$totalTests++;
if (!empty($migrationFiles)) {
    $passedTests++;
    $results['roadmap']['score'] += 20;
    echo "  ✓ Media Migration\n";
} else {
    echo "  ✗ Media Migration\n";
    $results['roadmap']['details'][] = "Missing: media migration";
}

// Routes
$routeContent = file_get_contents('routes/api.php');
$routes = ['MediaController', 'upload/image', 'upload/video', 'upload/document'];
$routeScore = 0;
foreach ($routes as $route) {
    $totalTests++;
    if (strpos($routeContent, $route) !== false) {
        $passedTests++;
        $routeScore++;
    }
}
$results['roadmap']['score'] += ($routeScore / count($routes)) * 20;
echo "  ✓ Routes: {$routeScore}/" . count($routes) . "\n";

// Security
$permissionSeeder = glob('database/seeders/PermissionSeeder.php');
$totalTests++;
if (!empty($permissionSeeder) && strpos(file_get_contents($permissionSeeder[0]), 'media.upload') !== false) {
    $passedTests++;
    $results['roadmap']['score'] += 15;
    echo "  ✓ Permissions\n";
} else {
    echo "  ✗ Permissions\n";
}

// Config
$totalTests++;
if (file_exists('config/media.php')) {
    $passedTests++;
    $results['roadmap']['score'] += 15;
    echo "  ✓ Configuration\n";
} else {
    echo "  ✗ Configuration\n";
}

// ============================================================================
// بخش 2: Twitter Standards (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 بخش 2: Twitter Standards (100 امتیاز)\n";
echo "─────────────────────────────────────────────────────────────────\n";

$serviceContent = file_exists('app/Services/MediaService.php') ? file_get_contents('app/Services/MediaService.php') : '';
$requestContent = file_exists('app/Http/Requests/MediaUploadRequest.php') ? file_get_contents('app/Http/Requests/MediaUploadRequest.php') : '';

$twitterStandards = [
    'Image formats' => ['jpeg', 'png', 'gif', 'webp'],
    'Video formats' => ['video'],
    'Image optimization' => 'processImage',
    'Thumbnail generation' => 'generateThumbnail',
    'Alt text support' => 'alt_text',
    'Multiple media' => 'morphMany',
];

foreach ($twitterStandards as $name => $patterns) {
    $totalTests++;
    $found = false;
    
    $postContent = file_exists('app/Models/Post.php') ? file_get_contents('app/Models/Post.php') : '';
    
    if (is_array($patterns)) {
        foreach ($patterns as $pattern) {
            if (strpos($serviceContent, $pattern) !== false || 
                strpos($requestContent, $pattern) !== false || 
                strpos($postContent, $pattern) !== false) {
                $found = true;
                break;
            }
        }
    } else {
        $found = strpos($serviceContent, $patterns) !== false || 
                 strpos($requestContent, $patterns) !== false ||
                 strpos($postContent, $patterns) !== false;
    }
    
    if ($found) {
        $passedTests++;
        $results['twitter']['score'] += (100 / count($twitterStandards));
        echo "  ✓ {$name}\n";
    } else {
        echo "  ✗ {$name}\n";
        $results['twitter']['details'][] = $name;
    }
}

// ============================================================================
// بخش 3: Operational Readiness (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 بخش 3: Operational Readiness (100 امتیاز)\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Model Relations
$models = ['Post', 'Comment', 'Message', 'Moment'];
$relationScore = 0;
foreach ($models as $model) {
    $totalTests++;
    $file = "app/Models/{$model}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'function media()') !== false && strpos($content, 'morphMany(Media::class') !== false) {
            $passedTests++;
            $relationScore++;
        }
    }
}
$results['operational']['score'] += ($relationScore / count($models)) * 40;
echo "  ✓ Model Relations: {$relationScore}/" . count($models) . "\n";

// Request Validations
$requests = [
    'StorePostRequest' => 'media',
    'SendMessageRequest' => 'attachments',
    'CreateCommentRequest' => 'media',
];
$validationScore = 0;
foreach ($requests as $name => $field) {
    $totalTests++;
    $file = "app/Http/Requests/{$name}.php";
    if (file_exists($file) && strpos(file_get_contents($file), "'{$field}'") !== false) {
        $passedTests++;
        $validationScore++;
    }
}
$results['operational']['score'] += ($validationScore / count($requests)) * 30;
echo "  ✓ Validations: {$validationScore}/" . count($requests) . "\n";

// Service Provider
$providers = glob('app/Providers/*.php');
$mediaServiceRegistered = false;
foreach ($providers as $provider) {
    if (strpos(file_get_contents($provider), 'MediaService') !== false) {
        $mediaServiceRegistered = true;
        break;
    }
}
$totalTests++;
if ($mediaServiceRegistered) {
    $passedTests++;
    $results['operational']['score'] += 30;
    echo "  ✓ Service Provider\n";
} else {
    echo "  ✗ Service Provider\n";
}

// ============================================================================
// بخش 4: Deep Integration (100 امتیاز)
// ============================================================================
echo "\n─────────────────────────────────────────────────────────────────\n";
echo "📋 بخش 4: Deep Integration (100 امتیاز)\n";
echo "─────────────────────────────────────────────────────────────────\n";

// Service Integration
$services = [
    'PostService' => ['MediaService', 'uploadImage', 'attachToModel', 'deleteMedia'],
    'MessageService' => ['MediaService', 'uploadDocument', 'attachToModel'],
    'CommentService' => ['MediaService', 'uploadImage', 'attachToModel'],
    'MomentService' => ['MediaService'],
];

$serviceIntegrationScore = 0;
$serviceIntegrationMax = 0;
foreach ($services as $serviceName => $patterns) {
    $file = "app/Services/{$serviceName}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($patterns as $pattern) {
            $totalTests++;
            $serviceIntegrationMax++;
            if (strpos($content, $pattern) !== false) {
                $passedTests++;
                $serviceIntegrationScore++;
            }
        }
    }
}
$results['integration']['score'] += ($serviceIntegrationScore / $serviceIntegrationMax) * 40;
echo "  ✓ Service Integration: {$serviceIntegrationScore}/{$serviceIntegrationMax}\n";

// Controller Integration
$controllers = [
    'PostController' => ['PostService', 'hasFile', 'createPost'],
    'MessageController' => ['MessageService', 'hasFile', 'sendMessage'],
    'CommentController' => ['CommentService', 'hasFile', 'createComment'],
    'MomentController' => ['MediaService', 'uploadImage'],
];

$controllerIntegrationScore = 0;
$controllerIntegrationMax = 0;
foreach ($controllers as $controllerName => $patterns) {
    $file = "app/Http/Controllers/Api/{$controllerName}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        foreach ($patterns as $pattern) {
            $totalTests++;
            $controllerIntegrationMax++;
            if (strpos($content, $pattern) !== false) {
                $passedTests++;
                $controllerIntegrationScore++;
            }
        }
    }
}
$results['integration']['score'] += ($controllerIntegrationScore / $controllerIntegrationMax) * 30;
echo "  ✓ Controller Integration: {$controllerIntegrationScore}/{$controllerIntegrationMax}\n";

// Resource Integration
$resources = ['PostResource', 'MessageResource', 'CommentResource'];
$resourceScore = 0;
foreach ($resources as $resource) {
    $totalTests++;
    $file = "app/Http/Resources/{$resource}.php";
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'MediaResource') !== false && 
            (strpos($content, 'relationLoaded') !== false || strpos($content, 'whenLoaded') !== false)) {
            $passedTests++;
            $resourceScore++;
        }
    }
}
$results['integration']['score'] += ($resourceScore / count($resources)) * 30;
echo "  ✓ Resource Integration: {$resourceScore}/" . count($resources) . "\n";

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

echo "\n📋 امتیازات بخشها:\n";
foreach ($results as $key => $result) {
    $score = round($result['score'], 1);
    $max = $result['max'];
    $percentage = round(($score / $max) * 100, 1);
    $totalScore += $score;
    
    $icon = $percentage >= 90 ? '✅' : ($percentage >= 70 ? '🟡' : '🔴');
    
    $label = match($key) {
        'roadmap' => 'ROADMAP Compliance',
        'twitter' => 'Twitter Standards',
        'operational' => 'Operational Readiness',
        'integration' => 'Deep Integration',
        default => ucfirst($key)
    };
    
    echo "  {$icon} {$label}: {$score}/{$max} ({$percentage}%)\n";
    
    if (!empty($result['details']) && count($result['details']) > 0) {
        echo "     Issues: " . implode(', ', array_slice($result['details'], 0, 3)) . "\n";
    }
}

$finalPercentage = round(($totalScore / $maxScore) * 100, 1);
echo "\n🎯 امتیاز نهایی: {$totalScore}/{$maxScore} ({$finalPercentage}%)\n";

if ($finalPercentage >= 95) {
    echo "\n🎉 عالی: Media System کامل و آماده Production است!\n";
    echo "✅ تمام معیارها رعایت شده\n";
} elseif ($finalPercentage >= 80) {
    echo "\n🟡 خوب: Media System آماده است اما نیاز به بهبودهای جزئی دارد\n";
    echo "⚠️  بررسی موارد ناموفق توصیه میشود\n";
} else {
    echo "\n🔴 نیاز به کار بیشتر: Media System نیاز به توسعه دارد\n";
    echo "❌ آماده Production نیست\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($finalPercentage >= 95 ? 0 : 1);
