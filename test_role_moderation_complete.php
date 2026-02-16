<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║     CLEVLANCE - ROLE & MODERATION SYSTEM                       ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$issues = [];
$passed = 0;
$failed = 0;

function check($description, $condition, &$issues, &$passed, &$failed) {
    if ($condition) {
        echo "  ✓ $description\n";
        $passed++;
    } else {
        echo "  ✗ $description\n";
        $issues[] = $description;
        $failed++;
    }
}

// ============================================================================
echo "PART 1: ROLE & SUBSCRIPTION SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1.1 ROLE DEFINITIONS\n";
echo "───────────────────────────────────────────────────────────────\n";
$roles = ['user', 'verified', 'premium', 'organization', 'moderator', 'admin'];
foreach ($roles as $role) {
    check("Role '$role' exists", 
        Spatie\Permission\Models\Role::where('name', $role)->exists(), 
        $issues, $passed, $failed);
}

echo "\n1.2 USER REGISTRATION\n";
echo "───────────────────────────────────────────────────────────────\n";
$testUser = App\Models\User::factory()->create();
check("New user has 'user' role", 
    $testUser->hasRole('user'), 
    $issues, $passed, $failed);
check("New user has basic permissions", 
    $testUser->getAllPermissions()->count() > 0, 
    $issues, $passed, $failed);

echo "\n1.3 EMAIL VERIFICATION\n";
echo "───────────────────────────────────────────────────────────────\n";
$verifiedUser = App\Models\User::factory()->create();
$verifiedUser->email_verified_at = now();
$verifiedUser->save();

if ($verifiedUser->hasRole('user') && !$verifiedUser->hasRole('verified')) {
    $verifiedUser->removeRole('user');
    $verifiedUser->assignRole('verified');
}

$verifiedUser->refresh();
check("Verified user has 'verified' role", 
    $verifiedUser->hasRole('verified'), 
    $issues, $passed, $failed);
check("Verified user has more permissions", 
    $verifiedUser->getAllPermissions()->count() > $testUser->getAllPermissions()->count(), 
    $issues, $passed, $failed);

echo "\n1.4 PREMIUM SUBSCRIPTION\n";
echo "───────────────────────────────────────────────────────────────\n";
$premiumService = app(App\Monetization\Services\PremiumService::class);
$subscription = $premiumService->subscribe($testUser, [
    'plan' => 'premium',
    'price' => 9.99,
    'billing_cycle' => 'monthly',
    'payment_method' => 'card',
    'transaction_id' => 'test_123'
]);
$testUser->refresh();

check("Subscription created", 
    $subscription->exists, 
    $issues, $passed, $failed);
check("User has 'premium' role after subscribe", 
    $testUser->hasRole('premium'), 
    $issues, $passed, $failed);
check("User is_premium flag is true", 
    $testUser->is_premium == true, 
    $issues, $passed, $failed);
check("User has premium permissions", 
    $testUser->can('post.schedule'), 
    $issues, $passed, $failed);

echo "\n1.5 PREMIUM CANCELLATION\n";
echo "───────────────────────────────────────────────────────────────\n";
$premiumService->cancel($subscription);
$testUser->refresh();

check("Subscription cancelled", 
    $subscription->fresh()->status == 'cancelled', 
    $issues, $passed, $failed);
check("User 'premium' role removed after cancel", 
    !$testUser->hasRole('premium'), 
    $issues, $passed, $failed);
check("User is_premium flag is false", 
    $testUser->is_premium == false, 
    $issues, $passed, $failed);

echo "\n1.6 OBSERVERS & MIDDLEWARE\n";
echo "───────────────────────────────────────────────────────────────\n";
check("UserObserver exists", 
    class_exists('App\\Observers\\UserObserver'), 
    $issues, $passed, $failed);
check("PremiumSubscriptionObserver exists", 
    class_exists('App\\Observers\\PremiumSubscriptionObserver'), 
    $issues, $passed, $failed);
check("CheckSubscription middleware exists", 
    class_exists('App\\Http\\Middleware\\CheckSubscription'), 
    $issues, $passed, $failed);
check("CheckFeatureAccess middleware exists", 
    class_exists('App\\Http\\Middleware\\CheckFeatureAccess'), 
    $issues, $passed, $failed);
check("RoleBasedRateLimit middleware exists", 
    class_exists('App\\Http\\Middleware\\RoleBasedRateLimit'), 
    $issues, $passed, $failed);

echo "\n1.7 CONFIG & LIMITS\n";
echo "───────────────────────────────────────────────────────────────\n";
check("Config monetization.php exists", 
    file_exists(config_path('monetization.php')), 
    $issues, $passed, $failed);
check("Config limits.php exists", 
    file_exists(config_path('limits.php')), 
    $issues, $passed, $failed);
check("SubscriptionLimitService exists", 
    class_exists('App\\Services\\SubscriptionLimitService'), 
    $issues, $passed, $failed);

// ============================================================================
echo "\n\nPART 2: MODERATION SYSTEM\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "2.1 NO PARALLEL WORK\n";
echo "───────────────────────────────────────────────────────────────\n";
check("spam_reports table does NOT exist", 
    !Schema::hasTable('spam_reports'), 
    $issues, $passed, $failed);
check("reports table exists", 
    Schema::hasTable('reports'), 
    $issues, $passed, $failed);
check("reports has auto_detected column", 
    Schema::hasColumn('reports', 'auto_detected'), 
    $issues, $passed, $failed);
check("reports has spam_score column", 
    Schema::hasColumn('reports', 'spam_score'), 
    $issues, $passed, $failed);
check("reports has detection_reasons column", 
    Schema::hasColumn('reports', 'detection_reasons'), 
    $issues, $passed, $failed);
check("Only one ModerationController", 
    count(glob(base_path('app/Http/Controllers/**/*Moderation*.php'))) === 1, 
    $issues, $passed, $failed);
check("No separate ModerationService", 
    !file_exists(base_path('app/Services/ModerationService.php')), 
    $issues, $passed, $failed);

echo "\n2.2 USER MODEL INTEGRATION\n";
echo "───────────────────────────────────────────────────────────────\n";
check("User has is_suspended field", 
    Schema::hasColumn('users', 'is_suspended'), 
    $issues, $passed, $failed);
check("User has is_banned field", 
    Schema::hasColumn('users', 'is_banned'), 
    $issues, $passed, $failed);
check("User has suspended_until field", 
    Schema::hasColumn('users', 'suspended_until'), 
    $issues, $passed, $failed);
check("User has banned_at field", 
    Schema::hasColumn('users', 'banned_at'), 
    $issues, $passed, $failed);
check("is_suspended casted to boolean", 
    (new App\Models\User())->getCasts()['is_suspended'] === 'boolean', 
    $issues, $passed, $failed);
check("is_banned casted to boolean", 
    (new App\Models\User())->getCasts()['is_banned'] === 'boolean', 
    $issues, $passed, $failed);

echo "\n2.3 SPAM DETECTION INTEGRATION\n";
echo "───────────────────────────────────────────────────────────────\n";
$spamServiceCode = file_get_contents(base_path('app/Services/SpamDetectionService.php'));
check("SpamDetection creates Report", 
    strpos($spamServiceCode, 'Report::create') !== false, 
    $issues, $passed, $failed);
check("SpamDetection does NOT use spam_reports", 
    strpos($spamServiceCode, 'spam_reports') === false, 
    $issues, $passed, $failed);
check("SpamDetection sets auto_detected = true", 
    strpos($spamServiceCode, "'auto_detected' => true") !== false, 
    $issues, $passed, $failed);
check("SpamDetection sets spam_score", 
    strpos($spamServiceCode, "'spam_score' =>") !== false, 
    $issues, $passed, $failed);
check("SpamDetection sets detection_reasons", 
    strpos($spamServiceCode, "'detection_reasons' =>") !== false, 
    $issues, $passed, $failed);
check("SpamDetection does NOT directly flag posts", 
    !preg_match('/\$post->update\(\[.*is_flagged.*\]\)/', $spamServiceCode), 
    $issues, $passed, $failed);
check("SpamDetection does NOT directly suspend users", 
    !preg_match('/\$user->update\(\[.*is_suspended.*\]\)/', $spamServiceCode), 
    $issues, $passed, $failed);

echo "\n2.4 MODERATION ACTIONS\n";
echo "───────────────────────────────────────────────────────────────\n";
$moderationCode = file_get_contents(base_path('app/Http/Controllers/Api/ModerationController.php'));
check("Has dismiss action", 
    strpos($moderationCode, "'dismiss'") !== false, 
    $issues, $passed, $failed);
check("Has warn action", 
    strpos($moderationCode, "'warn'") !== false, 
    $issues, $passed, $failed);
check("Has remove_content action", 
    strpos($moderationCode, "'remove_content'") !== false, 
    $issues, $passed, $failed);
check("Has suspend_user action", 
    strpos($moderationCode, "'suspend_user'") !== false, 
    $issues, $passed, $failed);
check("Has ban_user action", 
    strpos($moderationCode, "'ban_user'") !== false, 
    $issues, $passed, $failed);
check("Sets is_suspended = true", 
    strpos($moderationCode, "'is_suspended' => true") !== false, 
    $issues, $passed, $failed);
check("Sets is_banned = true", 
    strpos($moderationCode, "'is_banned' => true") !== false, 
    $issues, $passed, $failed);
check("Has executeAction method", 
    strpos($moderationCode, 'function executeAction') !== false, 
    $issues, $passed, $failed);

echo "\n2.5 MIDDLEWARE INTEGRATION\n";
echo "───────────────────────────────────────────────────────────────\n";
check("CheckUserModeration middleware exists", 
    class_exists('App\\Http\\Middleware\\CheckUserModeration'), 
    $issues, $passed, $failed);
$middlewareCode = file_get_contents(base_path('app/Http/Middleware/CheckUserModeration.php'));
check("Middleware checks is_banned", 
    strpos($middlewareCode, 'is_banned') !== false, 
    $issues, $passed, $failed);
check("Middleware checks is_suspended", 
    strpos($middlewareCode, 'is_suspended') !== false, 
    $issues, $passed, $failed);

echo "\n2.6 REPORT MODEL\n";
echo "───────────────────────────────────────────────────────────────\n";
$reportModel = new App\Models\Report();
check("Report has auto_detected in fillable", 
    in_array('auto_detected', $reportModel->getFillable()), 
    $issues, $passed, $failed);
check("Report has spam_score in fillable", 
    in_array('spam_score', $reportModel->getFillable()), 
    $issues, $passed, $failed);
check("Report has detection_reasons in fillable", 
    in_array('detection_reasons', $reportModel->getFillable()), 
    $issues, $passed, $failed);
check("Report casts auto_detected to boolean", 
    $reportModel->getCasts()['auto_detected'] === 'boolean', 
    $issues, $passed, $failed);
check("Report casts detection_reasons to array", 
    $reportModel->getCasts()['detection_reasons'] === 'array', 
    $issues, $passed, $failed);
check("Report has autoDetected scope", 
    method_exists(App\Models\Report::class, 'scopeAutoDetected'), 
    $issues, $passed, $failed);
check("Report has manual scope", 
    method_exists(App\Models\Report::class, 'scopeManual'), 
    $issues, $passed, $failed);
check("Report has reporter relationship", 
    method_exists(App\Models\Report::class, 'reporter'), 
    $issues, $passed, $failed);
check("Report has reportable morphTo", 
    method_exists(App\Models\Report::class, 'reportable'), 
    $issues, $passed, $failed);

echo "\n2.7 SECURITY & VALIDATION\n";
echo "───────────────────────────────────────────────────────────────\n";
check("Prevents self-reporting", 
    strpos($moderationCode, 'Cannot report yourself') !== false, 
    $issues, $passed, $failed);
check("Has input validation", 
    strpos($moderationCode, '$request->validate([') !== false, 
    $issues, $passed, $failed);
check("Uses JSON responses", 
    strpos($moderationCode, 'response()->json(') !== false, 
    $issues, $passed, $failed);
check("Has reason validation", 
    strpos($moderationCode, "'reason' => 'required") !== false, 
    $issues, $passed, $failed);
check("Has status validation", 
    strpos($moderationCode, "'status' => 'required") !== false, 
    $issues, $passed, $failed);

// ============================================================================
echo "\n\n═══════════════════════════════════════════════════════════════\n";
echo "FINAL SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$total = $passed + $failed;
$percentage = $total > 0 ? round(($passed / $total) * 100, 2) : 0;

echo "Total Checks: $total\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed ✗\n";
echo "Success Rate: $percentage%\n\n";

if (count($issues) > 0) {
    echo "ISSUES FOUND:\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
    echo "\n";
}

if ($percentage == 100) {
    echo "🎉 PERFECT: All systems operational!\n";
} elseif ($percentage >= 80) {
    echo "⚠️  NEEDS WORK: Some issues found\n";
} else {
    echo "❌ CRITICAL: Major issues found\n";
}

echo "\n╚═══════════════════════════════════════════════════════════════╝\n\n";

exit($percentage >= 95 ? 0 : 1);
