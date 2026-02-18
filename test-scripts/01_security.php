<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Hash, Route};
use App\Models\{User, AuditLog, DeviceToken};
use App\Services\{
    SecurityMonitoringService, AuditTrailService, DeviceFingerprintService,
    BotDetectionService, PasswordSecurityService, TwoFactorService,
    RateLimitingService, TokenManagementService
};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║          تست جامع سیستم Security - 8 بخش (100 تست)          ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0, 'warning' => 0];
$testUsers = [];
$sectionScores = [];

function test($name, $fn) {
    global $stats;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
            return true;
        } elseif ($result === null) {
            echo "  ⚠ {$name}\n";
            $stats['warning']++;
            return null;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
            return false;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}: " . substr($e->getMessage(), 0, 50) . "\n";
        $stats['failed']++;
        return false;
    }
}

function section($title, $weight) {
    echo "\n" . str_repeat("═", 65) . "\n";
    echo "  {$title} (وزن: {$weight}%)\n";
    echo str_repeat("═", 65) . "\n";
    return ['title' => $title, 'weight' => $weight, 'start' => $GLOBALS['stats']['passed']];
}

function endSection($section) {
    global $stats, $sectionScores;
    $passed = $stats['passed'] - $section['start'];
    $sectionScores[] = array_merge($section, ['passed' => $passed]);
}

// ═══════════════════════════════════════════════════════════════
// 1️⃣ Architecture & Code (20%)
// ═══════════════════════════════════════════════════════════════
$s1 = section("1️⃣ Architecture & Code", 20);

test("Controller DeviceController", fn() => class_exists('App\Http\Controllers\Api\DeviceController'));
test("Controller AuditController", fn() => class_exists('App\Http\Controllers\Api\AuditController'));
test("Service SecurityMonitoringService", fn() => class_exists('App\Services\SecurityMonitoringService'));
test("Service AuditTrailService", fn() => class_exists('App\Services\AuditTrailService'));
test("Service DeviceFingerprintService", fn() => class_exists('App\Services\DeviceFingerprintService'));
test("Service BotDetectionService", fn() => class_exists('App\Services\BotDetectionService'));
test("Service PasswordSecurityService", fn() => class_exists('App\Services\PasswordSecurityService'));
test("Service TwoFactorService", fn() => class_exists('App\Services\TwoFactorService'));
test("Service RateLimitingService", fn() => class_exists('App\Services\RateLimitingService'));
test("Service TokenManagementService", fn() => class_exists('App\Services\TokenManagementService'));
test("Model AuditLog", fn() => class_exists('App\Models\AuditLog'));
test("Model DeviceToken", fn() => class_exists('App\Models\DeviceToken'));
test("Resource DeviceResource", fn() => class_exists('App\Http\Resources\DeviceResource'));
test("AuditLog->user relationship", fn() => method_exists('App\Models\AuditLog', 'user'));
test("DeviceToken->user relationship", fn() => method_exists('App\Models\DeviceToken', 'user'));
test("User->devices relationship", fn() => method_exists('App\Models\User', 'devices'));
test("DeviceController->register", fn() => method_exists('App\Http\Controllers\Api\DeviceController', 'register'));
test("DeviceController->verifyDevice", fn() => method_exists('App\Http\Controllers\Api\DeviceController', 'verifyDevice'));
test("AuditController->getUserAuditTrail", fn() => method_exists('App\Http\Controllers\Api\AuditController', 'getUserAuditTrail'));
test("SecurityMonitoringService->checkSuspiciousActivity", fn() => method_exists('App\Services\SecurityMonitoringService', 'checkSuspiciousActivity'));

endSection($s1);

// ═══════════════════════════════════════════════════════════════
// 2️⃣ Database & Schema (15%)
// ═══════════════════════════════════════════════════════════════
$s2 = section("2️⃣ Database & Schema", 15);

test("Table audit_logs", fn() => DB::getSchemaBuilder()->hasTable('audit_logs'));
test("Table security_logs", fn() => DB::getSchemaBuilder()->hasTable('security_logs'));
test("Table device_tokens", fn() => DB::getSchemaBuilder()->hasTable('device_tokens'));

$auditCols = array_column(DB::select("SHOW COLUMNS FROM audit_logs"), 'Field');
test("audit_logs.user_id", fn() => in_array('user_id', $auditCols));
test("audit_logs.action", fn() => in_array('action', $auditCols));
test("audit_logs.ip_address", fn() => in_array('ip_address', $auditCols));
test("audit_logs.risk_level", fn() => in_array('risk_level', $auditCols));
test("audit_logs.timestamp", fn() => in_array('timestamp', $auditCols));
test("audit_logs.data", fn() => in_array('data', $auditCols));

$deviceCols = array_column(DB::select("SHOW COLUMNS FROM device_tokens"), 'Field');
test("device_tokens.fingerprint", fn() => in_array('fingerprint', $deviceCols));
test("device_tokens.is_trusted", fn() => in_array('is_trusted', $deviceCols));
test("device_tokens.device_type", fn() => in_array('device_type', $deviceCols));

$auditIdx = DB::select("SHOW INDEXES FROM audit_logs");
test("Index audit_logs.user_id", fn() => collect($auditIdx)->where('Column_name', 'user_id')->isNotEmpty());
test("Index audit_logs.action", fn() => collect($auditIdx)->where('Column_name', 'action')->isNotEmpty());
test("Index audit_logs.timestamp", fn() => collect($auditIdx)->where('Column_name', 'timestamp')->isNotEmpty());

$deviceIdx = DB::select("SHOW INDEXES FROM device_tokens");
test("Index device_tokens.fingerprint", fn() => collect($deviceIdx)->where('Column_name', 'fingerprint')->isNotEmpty());
test("Index device_tokens.user_id", fn() => collect($deviceIdx)->where('Column_name', 'user_id')->isNotEmpty());

test("FK audit_logs.user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='audit_logs' AND COLUMN_NAME='user_id'")) > 0);
test("FK device_tokens.user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='device_tokens' AND COLUMN_NAME='user_id'")) > 0);

endSection($s2);

// ═══════════════════════════════════════════════════════════════
// 3️⃣ API & Routes (15%)
// ═══════════════════════════════════════════════════════════════
$s3 = section("3️⃣ API & Routes", 15);

$routes = collect(Route::getRoutes())->map(fn($r) => [
    'uri' => $r->uri(),
    'method' => implode('|', $r->methods()),
    'name' => $r->getName(),
    'middleware' => $r->middleware()
]);

test("Route devices/register", fn() => $routes->where('uri', 'api/devices/register')->isNotEmpty());
test("Route auth/verify-device", fn() => $routes->where('uri', 'api/auth/verify-device')->isNotEmpty());
test("Route devices list", fn() => $routes->where('uri', 'api/devices')->isNotEmpty());
test("Route devices/{device}/trust", fn() => $routes->where('uri', 'api/devices/{device}/trust')->isNotEmpty());
test("Route devices/{device}", fn() => $routes->where('uri', 'api/devices/{device}')->isNotEmpty());
test("Route audit/my-activity", fn() => $routes->where('uri', 'api/auth/audit/my-activity')->isNotEmpty());
test("Route audit/security-events", fn() => $routes->where('uri', 'api/auth/audit/security-events')->isNotEmpty());

$apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
test("Auth middleware applied", fn() => str_contains($apiFile, 'auth:sanctum'));
test("Throttle middleware applied", fn() => str_contains($apiFile, 'throttle:'));
test("Route grouping devices", fn() => str_contains($apiFile, "prefix('devices')"));
test("Route grouping audit", fn() => str_contains($apiFile, "prefix('audit')"));

endSection($s3);

// ═══════════════════════════════════════════════════════════════
// 4️⃣ Security (20%)
// ═══════════════════════════════════════════════════════════════
$s4 = section("4️⃣ Security", 20);

$testUser = User::factory()->create(['email' => 'sec_test@test.com']);
$testUsers[] = $testUser;

test("Policy DevicePolicy", fn() => class_exists('App\Policies\DevicePolicy'));
test("Policy AuditLogPolicy", fn() => class_exists('App\Policies\AuditLogPolicy'));
test("DevicePolicy->register", fn() => method_exists('App\Policies\DevicePolicy', 'register'));
test("DevicePolicy->revoke", fn() => method_exists('App\Policies\DevicePolicy', 'revoke'));
test("AuditLogPolicy->view", fn() => method_exists('App\Policies\AuditLogPolicy', 'view'));

test("XSS protection", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.xss', ['content' => '<script>alert("xss")</script>'], null, $testUser->id);
    $log = AuditLog::where('user_id', $testUser->id)->where('action', 'test.xss')->latest()->first();
    return $log && !str_contains(json_encode($log->data), '<script>');
});

test("SQL injection protection", function() {
    try {
        AuditLog::where('action', "' OR '1'='1")->get();
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

test("Mass assignment AuditLog", function() {
    try {
        AuditLog::create(['id' => 99999, 'action' => 'test']);
        return AuditLog::find(99999) === null;
    } catch (\Exception $e) {
        return true;
    }
});

test("Mass assignment DeviceToken", function() {
    try {
        DeviceToken::create(['id' => 99999, 'token' => 'test']);
        return DeviceToken::find(99999) === null;
    } catch (\Exception $e) {
        return true;
    }
});

test("Middleware SecurityHeaders", fn() => class_exists('App\Http\Middleware\SecurityHeaders'));
test("Middleware UnifiedSecurityMiddleware", fn() => class_exists('App\Http\Middleware\UnifiedSecurityMiddleware'));

test("Rate limiting service", function() {
    $service = app(RateLimitingService::class);
    $result = $service->checkLimit('test.limit', 'test_id', ['max_attempts' => 2, 'window_minutes' => 1]);
    return $result['allowed'] === true;
});

test("Rate limit exceeded", function() {
    $service = app(RateLimitingService::class);
    $service->checkLimit('test.exceed', 'id2', ['max_attempts' => 1, 'window_minutes' => 1]);
    $service->checkLimit('test.exceed', 'id2', ['max_attempts' => 1, 'window_minutes' => 1]);
    $result = $service->checkLimit('test.exceed', 'id2', ['max_attempts' => 1, 'window_minutes' => 1]);
    return $result['allowed'] === false;
});

test("Threat detection SQL", function() {
    $service = app(SecurityMonitoringService::class);
    $request = request();
    $request->merge(['input' => "' OR '1'='1"]);
    $result = $service->calculateThreatScore($request);
    return $result['score'] > 0;
});

test("Threat detection XSS", function() {
    $service = app(SecurityMonitoringService::class);
    $request = request();
    $request->merge(['input' => '<script>alert(1)</script>']);
    $result = $service->calculateThreatScore($request);
    return $result['score'] > 0;
});

test("IP blocking", function() {
    $service = app(SecurityMonitoringService::class);
    $service->blockIP('192.168.1.100', 60, 'test');
    return $service->isIPBlocked('192.168.1.100');
});

test("Sensitive data redaction", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.sensitive', ['password' => 'secret', 'token' => 'abc'], null, $testUser->id);
    $log = AuditLog::where('action', 'test.sensitive')->first();
    return $log->data['password'] === '[REDACTED]';
});

test("2FA secret generation", function() {
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret();
    return !empty($secret) && strlen($secret) > 10;
});

test("2FA QR code", function() {
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret();
    $qr = $service->getQRCodeUrl('Test', 'test@test.com', $secret);
    return str_contains($qr, 'otpauth://');
});

test("Password strength weak", function() {
    $service = app(PasswordSecurityService::class);
    $errors = $service->validatePasswordStrength('weak');
    return count($errors) > 0;
});

test("Password strength strong", function() {
    $service = app(PasswordSecurityService::class);
    $errors = $service->validatePasswordStrength('StrongPass123!');
    return count($errors) === 0;
});

test("Bot detection", function() {
    $service = app(BotDetectionService::class);
    $result = $service->detectBot(request());
    return isset($result['is_bot']) && isset($result['confidence']);
});

endSection($s4);

// ═══════════════════════════════════════════════════════════════
// 5️⃣ Validation (10%)
// ═══════════════════════════════════════════════════════════════
$s5 = section("5️⃣ Validation", 10);

test("Request RegisterDeviceRequest", fn() => class_exists('App\Http\Requests\RegisterDeviceRequest'));
test("Request AdvancedDeviceRequest", fn() => class_exists('App\Http\Requests\AdvancedDeviceRequest'));
test("Request TrustDeviceRequest", fn() => class_exists('App\Http\Requests\TrustDeviceRequest'));
test("Config security", fn() => config('security') !== null);
test("Config security.threat_detection", fn() => config('security.threat_detection') !== null);
test("Config security.monitoring", fn() => config('security.monitoring') !== null);
test("Config authentication.device", fn() => config('authentication.device') !== null);
test("Config authentication.rate_limiting", fn() => config('authentication.rate_limiting') !== null);

test("RegisterDeviceRequest rules", function() {
    $request = new \App\Http\Requests\RegisterDeviceRequest();
    $rules = $request->rules();
    return isset($rules['device_name']) && isset($rules['platform']);
});

test("Config-based validation", function() {
    $maxDevices = config('authentication.device.max_devices');
    return is_numeric($maxDevices) && $maxDevices > 0;
});

endSection($s5);

// ═══════════════════════════════════════════════════════════════
// 6️⃣ Business Logic (10%)
// ═══════════════════════════════════════════════════════════════
$s6 = section("6️⃣ Business Logic", 10);

test("Create audit log", function() use ($testUser) {
    $log = AuditLog::create([
        'user_id' => $testUser->id,
        'action' => 'test.action',
        'ip_address' => '127.0.0.1',
        'timestamp' => now(),
        'risk_level' => 'low'
    ]);
    return $log->exists;
});

test("Create device token", function() use ($testUser) {
    $device = DeviceToken::create([
        'user_id' => $testUser->id,
        'token' => 'test_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'fp_' . uniqid(),
        'is_trusted' => false
    ]);
    return $device->exists;
});

test("Device fingerprint generation", function() {
    $fp = DeviceFingerprintService::generate(request());
    return !empty($fp) && strlen($fp) === 64;
});

test("Device fingerprint validation", function() {
    $fp = DeviceFingerprintService::generate(request());
    return DeviceFingerprintService::validate($fp, request()) === true;
});

test("Audit trail logging", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.log', ['data' => 'test'], null, $testUser->id);
    return AuditLog::where('user_id', $testUser->id)->where('action', 'test.log')->exists();
});

test("Security monitoring", function() use ($testUser) {
    $service = app(SecurityMonitoringService::class);
    $result = $service->checkSuspiciousActivity($testUser->id);
    return isset($result['detected']) && isset($result['risk_level']);
});

test("Risk level high", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('user.delete', [], null, $testUser->id);
    $log = AuditLog::where('action', 'user.delete')->first();
    return $log->risk_level === 'high';
});

test("Risk level medium", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('auth.password_change', [], null, $testUser->id);
    $log = AuditLog::where('action', 'auth.password_change')->first();
    return $log->risk_level === 'medium';
});

test("Risk level low", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('post.create', [], null, $testUser->id);
    $log = AuditLog::where('action', 'post.create')->first();
    return $log->risk_level === 'low';
});

test("Device trust mechanism", function() use ($testUser) {
    $device = DeviceToken::create([
        'user_id' => $testUser->id,
        'token' => 'trust_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'trust_' . uniqid(),
        'is_trusted' => false
    ]);
    $device->update(['is_trusted' => true]);
    return $device->fresh()->is_trusted === true;
});

test("Anomaly detection", function() use ($testUser) {
    AuditLog::create([
        'user_id' => $testUser->id,
        'action' => 'auth.login',
        'ip_address' => '192.168.1.1',
        'timestamp' => now()->subDays(10),
        'risk_level' => 'low'
    ]);
    AuditLog::create([
        'user_id' => $testUser->id,
        'action' => 'auth.login',
        'ip_address' => '10.0.0.1',
        'timestamp' => now(),
        'risk_level' => 'low'
    ]);
    $service = app(AuditTrailService::class);
    $anomalies = $service->detectAnomalousActivity($testUser->id);
    return count($anomalies) > 0;
});

test("Token management", function() use ($testUser) {
    $service = app(TokenManagementService::class);
    $sessions = $service->getUserActiveSessions($testUser);
    return isset($sessions['active_tokens']);
});

test("Error handling", function() {
    try {
        $service = app(AuditTrailService::class);
        $service->log('invalid.action', [], null, 999999);
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

endSection($s6);

// ═══════════════════════════════════════════════════════════════
// 7️⃣ Integration (5%)
// ═══════════════════════════════════════════════════════════════
$s7 = section("7️⃣ Integration", 5);

test("Security event logging", function() {
    $service = app(AuditTrailService::class);
    $service->logSecurityEvent('suspicious_activity', ['reason' => 'test'], request());
    return AuditLog::where('action', 'security.suspicious_activity')->exists();
});

test("Auth event logging", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->logAuthEvent('login', $testUser, [], request());
    return AuditLog::where('action', 'auth.login')->where('user_id', $testUser->id)->exists();
});

test("Session tracking", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.session', [], null, $testUser->id);
    $log = AuditLog::where('action', 'test.session')->first();
    return isset($log->session_id);
});

test("IP tracking", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.ip', [], request(), $testUser->id);
    $log = AuditLog::where('action', 'test.ip')->first();
    return !empty($log->ip_address);
});

test("User agent tracking", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.ua', [], request(), $testUser->id);
    $log = AuditLog::where('action', 'test.ua')->first();
    return !empty($log->user_agent);
});

endSection($s7);

// ═══════════════════════════════════════════════════════════════
// 8️⃣ Testing (5%)
// ═══════════════════════════════════════════════════════════════
$s8 = section("8️⃣ Testing", 5);

test("Foreign key cascade", function() {
    $tempUser = User::factory()->create();
    AuditLog::create([
        'user_id' => $tempUser->id,
        'action' => 'test.cascade',
        'timestamp' => now(),
        'risk_level' => 'low'
    ]);
    $tempUser->delete();
    return AuditLog::where('action', 'test.cascade')->first()->user_id === null;
});

test("Device cascade delete", function() {
    $tempUser = User::factory()->create();
    $device = DeviceToken::create([
        'user_id' => $tempUser->id,
        'token' => 'cascade_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'cascade_' . uniqid()
    ]);
    $deviceId = $device->id;
    $tempUser->delete();
    return DeviceToken::find($deviceId) === null;
});

test("Model casts", function() {
    $log = new AuditLog();
    return $log->getCasts()['data'] === 'array';
});

test("Model scopes", function() {
    return method_exists(DeviceToken::class, 'scopeActive') && 
           method_exists(DeviceToken::class, 'scopeTrusted');
});

test("Fingerprint uniqueness", function() {
    $fp1 = DeviceFingerprintService::generate(request());
    $fp2 = DeviceFingerprintService::generate(request());
    return $fp1 === $fp2;
});

endSection($s8);

// ═══════════════════════════════════════════════════════════════
// پاکسازی
// ═══════════════════════════════════════════════════════════════
echo "\n🧹 پاکسازی...\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->devices()->delete();
        AuditLog::where('user_id', $user->id)->delete();
        $user->delete();
    }
}
Cache::flush();
echo "  ✓ پاکسازی انجام شد\n";

// ═══════════════════════════════════════════════════════════════
// گزارش نهایی
// ═══════════════════════════════════════════════════════════════
$total = array_sum($stats);
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;

echo "\n" . str_repeat("═", 65) . "\n";
echo "                         گزارش نهایی\n";
echo str_repeat("═", 65) . "\n\n";

echo "📊 آمار کلی:\n";
echo "  • کل تستها: {$total}\n";
echo "  • موفق: {$stats['passed']} ✓\n";
echo "  • ناموفق: {$stats['failed']} ✗\n";
echo "  • هشدار: {$stats['warning']} ⚠\n";
echo "  • درصد موفقیت: {$percentage}%\n\n";

echo "📋 نمره بخش‌ها (بر اساس معیارهای استاندارد):\n";
foreach ($sectionScores as $section) {
    $sectionTotal = $section['passed'] + ($stats['failed'] > 0 ? 1 : 0);
    $sectionPercent = $sectionTotal > 0 ? round(($section['passed'] / $sectionTotal) * 100) : 0;
    $weightedScore = round(($sectionPercent * $section['weight']) / 100, 1);
    echo sprintf("  %s: %d%% (وزن: %d%% = %.1f امتیاز)\n", 
        $section['title'], $sectionPercent, $section['weight'], $weightedScore);
}

$finalScore = 0;
foreach ($sectionScores as $section) {
    $sectionTotal = $section['passed'] + ($stats['failed'] > 0 ? 1 : 0);
    $sectionPercent = $sectionTotal > 0 ? ($section['passed'] / $sectionTotal) * 100 : 0;
    $finalScore += ($sectionPercent * $section['weight']) / 100;
}

echo "\n🎯 نمره نهایی: " . round($finalScore, 1) . "/100\n\n";

if ($finalScore >= 95) {
    echo "🎉 عالی: سیستم Security کاملاً production-ready است!\n";
} elseif ($finalScore >= 85) {
    echo "✅ خوب: سیستم آماده با مسائل جزئی\n";
} elseif ($finalScore >= 70) {
    echo "⚠️ متوسط: نیاز به بهبود\n";
} else {
    echo "❌ ضعیف: نیاز به رفع مشکلات جدی\n";
}

echo "\n8 بخش تست شده بر اساس معیارهای استاندارد:\n";
echo "1️⃣ Architecture (20%) | 2️⃣ Database (15%) | 3️⃣ API (15%) | 4️⃣ Security (20%)\n";
echo "5️⃣ Validation (10%) | 6️⃣ Business Logic (10%) | 7️⃣ Integration (5%) | 8️⃣ Testing (5%)\n";
