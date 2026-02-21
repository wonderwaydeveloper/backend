<?php

/**
 * Update Config References Script
 * 
 * This script updates all config('authentication.*') references to config('security.*')
 */

$replacements = [
    "config('authentication.password" => "config('security.password",
    "config('authentication.tokens" => "config('security.tokens",
    "config('authentication.session" => "config('security.session",
    "config('authentication.email" => "config('security.email",
    "config('authentication.device" => "config('security.device",
    "config('authentication.social" => "config('security.social",
    "config('authentication.age_restrictions" => "config('security.age_restrictions",
    "config('authentication.waf" => "config('security.waf",
    "config('authentication.rate_limiting" => "config('security.rate_limiting",
    "config('authentication.cache" => "config('performance.cache", // Will be updated in Phase 5
];

$files = [
    'app/Console/Commands/SecurityAudit.php',
    'app/Console/Commands/TestEmailTemplatesCommand.php',
    'app/Http/Controllers/Api/DeviceController.php',
    'app/Http/Controllers/Api/SocialAuthController.php',
    'app/Http/Middleware/SecurityHeaders.php',
    'app/Http/Middleware/UnifiedSecurityMiddleware.php',
    'app/Models/DeviceToken.php',
    'app/Notifications/ResetPasswordNotification.php',
    'app/Rules/MinimumAge.php',
    'app/Rules/SecureEmail.php',
    'app/Services/AuthService.php',
    'app/Services/CacheOptimizationService.php',
    'app/Services/DeviceFingerprintService.php',
    'app/Services/EmailService.php',
    'app/Services/PasswordSecurityService.php',
    'app/Services/SessionTimeoutService.php',
    'app/Services/SmsService.php',
    'app/Services/VerificationCodeService.php',
];

$basePath = __DIR__;
$updatedCount = 0;
$totalReplacements = 0;

echo "🔄 Starting config reference updates...\n\n";

foreach ($files as $file) {
    $fullPath = $basePath . '/' . $file;
    
    if (!file_exists($fullPath)) {
        echo "⚠️  File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($fullPath);
    $originalContent = $content;
    $fileReplacements = 0;
    
    foreach ($replacements as $old => $new) {
        $count = 0;
        $content = str_replace($old, $new, $content, $count);
        $fileReplacements += $count;
        $totalReplacements += $count;
    }
    
    if ($fileReplacements > 0) {
        file_put_contents($fullPath, $content);
        echo "✅ Updated $file ($fileReplacements replacements)\n";
        $updatedCount++;
    } else {
        echo "⏭️  Skipped $file (no changes needed)\n";
    }
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ Update completed!\n";
echo "   Files updated: $updatedCount/" . count($files) . "\n";
echo "   Total replacements: $totalReplacements\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
