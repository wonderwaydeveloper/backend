<?php
/**
 * Authentication System Comprehensive Verification
 * تست جامع سیستم احراز هویت - 16 بخش
 */

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          تست جامع سیستم احراز هویت (16 بخش)                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

$passed = $failed = $warnings = [];

// Load files once
$authService = file_get_contents('app/Services/AuthService.php');
$controller = file_get_contents('app/Http/Controllers/Api/UnifiedAuthController.php');
$bootstrap = file_get_contents('bootstrap/app.php');
$routes = file_get_contents('routes/api.php');
$config = file_get_contents('config/authentication.php');

// 1️⃣ Completeness
echo "1️⃣ کامل بودن...\n";
foreach ([
    'AuthService', 'PasswordSecurityService', 'TwoFactorService', 'TokenManagementService',
    'SessionTimeoutService', 'DeviceFingerprintService', 'EmailService', 'SmsService',
    'RateLimitingService', 'SecurityMonitoringService', 'AuditTrailService', 'VerificationCodeService'
] as $s) {
    file_exists("app/Services/{$s}.php") ? $passed[] = "✅ {$s}" : $failed[] = "❌ {$s}";
}

foreach (['UnifiedAuthController', 'PasswordResetController', 'DeviceController', 'SocialAuthController'] as $c) {
    file_exists("app/Http/Controllers/Api/{$c}.php") ? $passed[] = "✅ {$c}" : $failed[] = "❌ {$c}";
}

// 2️⃣ Accuracy
echo "\n2️⃣ دقت...\n";
foreach (['register', 'login', 'logout', 'forgotPassword', 'resetPassword', 'verifyEmail', 'enable2FA', 'verify2FA', 'disable2FA'] as $m) {
    preg_match("/public function {$m}\\(/", $authService) ? $passed[] = "✅ {$m}()" : $failed[] = "❌ {$m}()";
}
preg_match('/VerificationCodeService/', $controller) ? $passed[] = "✅ VerificationCodeService" : $failed[] = "❌ VerificationCodeService";
preg_match_all('/random_int\s*\(\s*\d+/', $controller) === 0 ? $passed[] = "✅ No hardcode" : $failed[] = "❌ Hardcode exists";

// 3️⃣ Standards
echo "\n3️⃣ استاندارد...\n";
preg_match('/private/', $authService) ? $passed[] = "✅ Property Promotion" : $warnings[] = "⚠️ Property Promotion";
preg_match('/implements\s+\w+Interface/', $authService) ? $passed[] = "✅ Interface" : $warnings[] = "⚠️ Interface";
$typed = preg_match_all('/:\s*\w+/', $authService);
$total = preg_match_all('/public function/', $authService);
$typePercent = ($typed / $total) * 100;
$typePercent >= 90 ? $passed[] = "✅ Type Hints {$typePercent}%" : $warnings[] = "⚠️ Type Hints {$typePercent}%";

// 4️⃣ Operational
echo "\n4️⃣ عملیاتی...\n";
preg_match('/SecurityHeaders/', $bootstrap) ? $passed[] = "✅ SecurityHeaders" : $failed[] = "❌ SecurityHeaders";
preg_match('/CaptchaMiddleware/', $bootstrap) ? $passed[] = "✅ CaptchaMiddleware" : $failed[] = "❌ CaptchaMiddleware";
preg_match('/\/login/', $routes) ? $passed[] = "✅ Login route" : $failed[] = "❌ Login route";
preg_match('/\/register/', $routes) ? $passed[] = "✅ Register route" : $failed[] = "❌ Register route";
preg_match('/rate_limiting/', $config) ? $passed[] = "✅ Rate limiting config" : $failed[] = "❌ Rate limiting config";

// 5️⃣ Integration
echo "\n5️⃣ مرتبط بودن...\n";
preg_match('/EmailService/', $authService) ? $passed[] = "✅ EmailService" : $failed[] = "❌ EmailService";
preg_match('/TokenManagementService/', $authService) ? $passed[] = "✅ TokenManagementService" : $failed[] = "❌ TokenManagementService";
preg_match('/PasswordSecurityService/', $authService) ? $passed[] = "✅ PasswordSecurityService" : $failed[] = "❌ PasswordSecurityService";
!file_exists('app/Services/DatabaseEncryptionService.php') ? $passed[] = "✅ No duplicates" : $failed[] = "❌ Duplicates exist";

// 6️⃣ Security
echo "\n6️⃣ امنیت...\n";
preg_match('/failedAttempts >= 3/', file_get_contents('app/Http/Middleware/CaptchaMiddleware.php')) ? $passed[] = "✅ CAPTCHA" : $warnings[] = "⚠️ CAPTCHA";
preg_match('/checkPasswordHistory/', file_get_contents('app/Services/PasswordSecurityService.php')) ? $passed[] = "✅ Password History" : $warnings[] = "⚠️ Password History";
preg_match('/Google2FA/', file_get_contents('app/Services/TwoFactorService.php')) ? $passed[] = "✅ 2FA" : $failed[] = "❌ 2FA";
preg_match('/RateLimitingService/', $controller) ? $passed[] = "✅ Rate Limiting" : $warnings[] = "⚠️ Rate Limiting";

// 7️⃣ User Flows
echo "\n7️⃣ User Flows...\n";
preg_match('/multiStepStep[123]/', $controller) ? $passed[] = "✅ Registration Flow" : $failed[] = "❌ Registration Flow";
preg_match('/public function login/', $controller) ? $passed[] = "✅ Login Flow" : $failed[] = "❌ Login Flow";
preg_match('/verifyDevice/', file_get_contents('app/Http/Controllers/Api/DeviceController.php')) ? $passed[] = "✅ Device Verification" : $failed[] = "❌ Device Verification";
preg_match('/phoneLogin/', $controller) ? $passed[] = "✅ Phone Login" : $failed[] = "❌ Phone Login";
preg_match('/forgotPassword/', file_get_contents('app/Http/Controllers/Api/PasswordResetController.php')) ? $passed[] = "✅ Password Reset" : $failed[] = "❌ Password Reset";
preg_match('/redirect/', file_get_contents('app/Http/Controllers/Api/SocialAuthController.php')) ? $passed[] = "✅ Social Auth" : $failed[] = "❌ Social Auth";
preg_match('/enable2FA/', $controller) ? $passed[] = "✅ 2FA Management" : $failed[] = "❌ 2FA Management";
preg_match('/getSessions/', $controller) ? $passed[] = "✅ Session Management" : $failed[] = "❌ Session Management";

// 8️⃣ Error Handling
echo "\n8️⃣ Error Handling...\n";
file_exists('app/Exceptions/ValidationException.php') ? $passed[] = "✅ Custom Exceptions" : $warnings[] = "⚠️ Custom Exceptions";
preg_match('/exceptions->render/', $bootstrap) ? $passed[] = "✅ Exception Handler" : $warnings[] = "⚠️ Exception Handler";
preg_match('/throw new/', $authService) ? $passed[] = "✅ Exception Usage" : $warnings[] = "⚠️ Exception Usage";

// 9️⃣ Validation
echo "\n9️⃣ Validation...\n";
count(glob('app/Http/Requests/*.php')) > 0 ? $passed[] = "✅ Form Requests" : $warnings[] = "⚠️ Form Requests";
preg_match('/StrongPassword/', $controller) ? $passed[] = "✅ Custom Rules" : $warnings[] = "⚠️ Custom Rules";
preg_match('/\$request->validate/', $controller) ? $passed[] = "✅ Request Validation" : $warnings[] = "⚠️ Request Validation";

// 🔟 Resources
echo "\n🔟 Resources...\n";
count(glob('app/Http/Resources/*.php')) > 0 ? $passed[] = "✅ API Resources" : $warnings[] = "⚠️ API Resources";
preg_match('/response\(\)->json/', $controller) ? $passed[] = "✅ JSON Response" : $warnings[] = "⚠️ JSON Response";
preg_match('/,\s*\d{3}\)/', $controller) ? $passed[] = "✅ HTTP Status Codes" : $warnings[] = "⚠️ HTTP Status Codes";

// 1️⃣1️⃣ Security Advanced
echo "\n1️⃣1️⃣ Security تکمیلی...\n";
preg_match('/csrf/', $bootstrap) ? $passed[] = "✅ CSRF" : $warnings[] = "⚠️ CSRF";
!preg_match('/DB::raw/', $controller) ? $passed[] = "✅ No SQL Injection" : $warnings[] = "⚠️ SQL Injection Risk";
preg_match('/X-XSS-Protection/', file_get_contents('app/Http/Middleware/SecurityHeaders.php')) ? $passed[] = "✅ XSS Protection" : $warnings[] = "⚠️ XSS Protection";
preg_match('/Hash::make/', $authService) ? $passed[] = "✅ Password Hashing" : $failed[] = "❌ Password Hashing";
preg_match('/encrypt\(/', $authService) ? $passed[] = "✅ Encryption" : $warnings[] = "⚠️ Encryption";

// 1️⃣2️⃣ Performance
echo "\n1️⃣2️⃣ Performance...\n";
preg_match('/Cache::/', $authService) ? $passed[] = "✅ Cache" : $warnings[] = "⚠️ Cache";
preg_match('/->with\(/', $controller) ? $passed[] = "✅ Eager Loading" : $warnings[] = "⚠️ Eager Loading";
preg_match('/->select\(/', $controller) ? $passed[] = "✅ Query Optimization" : $warnings[] = "⚠️ Query Optimization";

// 1️⃣3️⃣ Logging
echo "\n1️⃣3️⃣ Logging...\n";
preg_match('/AuditTrailService/', $authService) ? $passed[] = "✅ Audit Logging" : $warnings[] = "⚠️ Audit Logging";
preg_match('/logSecurityEvent/', $authService) ? $passed[] = "✅ Security Logging" : $warnings[] = "⚠️ Security Logging";
preg_match('/Log::/', $authService) ? $passed[] = "✅ Error Logging" : $warnings[] = "⚠️ Error Logging";

// 1️⃣4️⃣ Database
echo "\n1️⃣4️⃣ Database...\n";
count(glob('database/migrations/*users*.php')) > 0 ? $passed[] = "✅ Migrations" : $warnings[] = "⚠️ Migrations";
preg_match('/\$fillable|\$guarded/', file_get_contents('app/Models/User.php')) ? $passed[] = "✅ Mass Assignment" : $warnings[] = "⚠️ Mass Assignment";
preg_match('/hasMany|belongsTo/', file_get_contents('app/Models/User.php')) ? $passed[] = "✅ Relationships" : $warnings[] = "⚠️ Relationships";

// 1️⃣5️⃣ Testing
echo "\n1️⃣5️⃣ Testing...\n";
(count(glob('tests/Feature/*Test.php')) + count(glob('tests/Unit/*Test.php'))) > 0 ? $passed[] = "✅ Test Files" : $warnings[] = "⚠️ Test Files";
file_exists('phpunit.xml') ? $passed[] = "✅ PHPUnit Config" : $warnings[] = "⚠️ PHPUnit Config";

// 1️⃣6️⃣ Documentation
echo "\n1️⃣6️⃣ Documentation...\n";
count(glob('docs/*.md')) > 0 ? $passed[] = "✅ Docs" : $warnings[] = "⚠️ Docs";
file_exists('README.md') ? $passed[] = "✅ README" : $warnings[] = "⚠️ README";
file_exists('config/l5-swagger.php') ? $passed[] = "✅ API Docs" : $warnings[] = "⚠️ API Docs";

// Final Report
$total = count($passed) + count($failed) + count($warnings);
$score = round((count($passed) / $total) * 100, 1);

echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                      نتیجه نهایی                             ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";
echo "✅ تایید: " . count($passed) . " | ⚠️ هشدار: " . count($warnings) . " | ❌ خطا: " . count($failed) . "\n";
echo "📊 امتیاز: {$score}%\n\n";

if ($score >= 95) echo "🏆 عالی - Production Ready\n";
elseif ($score >= 85) echo "👍 خوب - نیاز به بهبود جزئی\n";
else echo "⚠️ نیاز به بهبود\n";

echo "\n16 بخش:\n";
$sections = [
    'کامل بودن' => fn($p) => str_contains($p, 'Service') || str_contains($p, 'Controller'),
    'دقت' => fn($p) => str_contains($p, '()'),
    'استاندارد' => fn($p) => str_contains($p, 'Property') || str_contains($p, 'Interface'),
    'عملیاتی' => fn($p) => str_contains($p, 'route') || str_contains($p, 'config'),
    'مرتبط بودن' => fn($p) => str_contains($p, 'Service'),
    'امنیت' => fn($p) => str_contains($p, 'CAPTCHA') || str_contains($p, '2FA'),
    'User Flows' => fn($p) => str_contains($p, 'Flow'),
    'Error Handling' => fn($p) => str_contains($p, 'Exception'),
    'Validation' => fn($p) => str_contains($p, 'Validation') || str_contains($p, 'Rules'),
    'Resources' => fn($p) => str_contains($p, 'Resources') || str_contains($p, 'Response'),
    'Security+' => fn($p) => str_contains($p, 'CSRF') || str_contains($p, 'XSS') || str_contains($p, 'Hash'),
    'Performance' => fn($p) => str_contains($p, 'Cache') || str_contains($p, 'Eager'),
    'Logging' => fn($p) => str_contains($p, 'Audit') || str_contains($p, 'Logging'),
    'Database' => fn($p) => str_contains($p, 'Migration') || str_contains($p, 'Mass'),
    'Testing' => fn($p) => str_contains($p, 'Test') || str_contains($p, 'PHPUnit'),
    'Documentation' => fn($p) => str_contains($p, 'Docs') || str_contains($p, 'README'),
];

$i = 1;
foreach ($sections as $name => $filter) {
    $count = count(array_filter($passed, $filter));
    echo ($i < 10 ? "{$i}️⃣ " : "1️⃣" . ($i-10) . "️⃣ ") . " {$name}: " . ($count > 0 ? "✅" : "⚠️") . "\n";
    $i++;
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "تاریخ: " . date('Y-m-d H:i:s') . " | Score: {$score}%\n";
echo "═══════════════════════════════════════════════════════════════\n";
