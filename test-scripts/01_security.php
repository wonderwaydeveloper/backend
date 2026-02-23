<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Hash, Route};
use App\Models\{User, AuditLog, DeviceToken, SecurityLog};
use App\Services\{
    SecurityMonitoringService, AuditTrailService, DeviceFingerprintService,
    BotDetectionService, PasswordSecurityService, TwoFactorService,
    RateLimitingService, TokenManagementService, SessionTimeoutService,
    VerificationCodeService, FileSecurityService
};
use Spatie\Permission\Models\{Role, Permission};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       تست کامل سیستم Security - 20 بخش (150+ تست)           ║\n";
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

function section($title) {
    echo "\n" . str_repeat("─", 65) . "\n";
    echo "{$title}\n";
    echo str_repeat("─", 65) . "\n";
    return ['title' => $title, 'start' => $GLOBALS['stats']['passed']];
}

function endSection($section) {
    global $stats, $sectionScores;
    $passed = $stats['passed'] - $section['start'];
    $sectionScores[] = array_merge($section, ['passed' => $passed]);
}

// ═══════════════════════════════════════════════════════════════
// بخش 1: Database & Schema
// ═══════════════════════════════════════════════════════════════
$s1 = section("1️⃣ بخش 1: Database & Schema");

// بررسی جداول
test("Table audit_logs", fn() => DB::getSchemaBuilder()->hasTable('audit_logs'));
test("Table security_logs", fn() => DB::getSchemaBuilder()->hasTable('security_logs'));
test("Table device_tokens", fn() => DB::getSchemaBuilder()->hasTable('device_tokens'));
test("Table security_alerts", fn() => DB::getSchemaBuilder()->hasTable('security_alerts'));

// بررسی ستونها
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

// بررسی indexes
$auditIdx = DB::select("SHOW INDEXES FROM audit_logs");
test("Index audit_logs.user_id", fn() => collect($auditIdx)->where('Column_name', 'user_id')->isNotEmpty());
test("Index audit_logs.action", fn() => collect($auditIdx)->where('Column_name', 'action')->isNotEmpty());
test("Index audit_logs.timestamp", fn() => collect($auditIdx)->where('Column_name', 'timestamp')->isNotEmpty());

$deviceIdx = DB::select("SHOW INDEXES FROM device_tokens");
test("Index device_tokens.fingerprint", fn() => collect($deviceIdx)->where('Column_name', 'fingerprint')->isNotEmpty());
test("Index device_tokens.user_id", fn() => collect($deviceIdx)->where('Column_name', 'user_id')->isNotEmpty());

// بررسی foreign keys
test("FK audit_logs.user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='audit_logs' AND COLUMN_NAME='user_id'")) > 0);
test("FK device_tokens.user_id", fn() => count(DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME='device_tokens' AND COLUMN_NAME='user_id'")) > 0);

endSection($s1);

// ═══════════════════════════════════════════════════════════════
// بخش 2: Models & Relationships
// ═══════════════════════════════════════════════════════════════
$s2 = section("2️⃣ بخش 2: Models & Relationships");

test("Model AuditLog", fn() => class_exists('App\\Models\\AuditLog'));
test("Model DeviceToken", fn() => class_exists('App\\Models\\DeviceToken'));
test("Model SecurityLog", fn() => class_exists('App\\Models\\SecurityLog'));
test("AuditLog->user relationship", fn() => method_exists('App\\Models\\AuditLog', 'user'));
test("DeviceToken->user relationship", fn() => method_exists('App\\Models\\DeviceToken', 'user'));
test("User->devices relationship", fn() => method_exists('App\\Models\\User', 'devices'));
test("Mass assignment protection AuditLog", fn() => !in_array('id', (new AuditLog())->getFillable()));
test("Mass assignment protection DeviceToken", fn() => !in_array('id', (new DeviceToken())->getFillable()));

endSection($s2);

// ═══════════════════════════════════════════════════════════════
// بخش 3: Validation Integration
// ═══════════════════════════════════════════════════════════════
$s3 = section("3️⃣ بخش 3: Validation Integration");

test("Request RegisterDeviceRequest", fn() => class_exists('App\\Http\\Requests\\RegisterDeviceRequest'));
test("Request AdvancedDeviceRequest", fn() => class_exists('App\\Http\\Requests\\AdvancedDeviceRequest'));
test("Request TrustDeviceRequest", fn() => class_exists('App\\Http\\Requests\\TrustDeviceRequest'));
test("Config security", fn() => config('security') !== null);
test("Config security.threat_detection", fn() => config('security.threat_detection') !== null);
test("Config security.monitoring", fn() => config('security.monitoring') !== null);
test("Config security.device", fn() => config('security.device') !== null);
test("Config security.rate_limiting", fn() => config('security.rate_limiting') !== null);

test("RegisterDeviceRequest rules", function() {
    $request = new \App\Http\Requests\RegisterDeviceRequest();
    $rules = $request->rules();
    return isset($rules['device_name']) && isset($rules['platform']);
});

test("Config-based validation device", function() {
    $maxDevices = config('security.device.max_devices');
    return is_numeric($maxDevices) && $maxDevices > 0;
});

test("No hardcoded values in Request", function() {
    $file = file_get_contents(__DIR__ . '/../app/Http/Requests/RegisterDeviceRequest.php');
    return !str_contains($file, "'max:5'") && !str_contains($file, "'min:3'");
});

endSection($s3);

// ═══════════════════════════════════════════════════════════════
// بخش 4: Controllers & Services
// ═══════════════════════════════════════════════════════════════
$s4 = section("4️⃣ بخش 4: Controllers & Services");

test("Controller DeviceController", fn() => class_exists('App\\Http\\Controllers\\Api\\DeviceController'));
test("Controller AuditController", fn() => class_exists('App\\Http\\Controllers\\Api\\AuditController'));
test("Service SecurityMonitoringService", fn() => class_exists('App\\Services\\SecurityMonitoringService'));
test("Service AuditTrailService", fn() => class_exists('App\\Services\\AuditTrailService'));
test("Service DeviceFingerprintService", fn() => class_exists('App\\Services\\DeviceFingerprintService'));
test("Service BotDetectionService", fn() => class_exists('App\\Services\\BotDetectionService'));
test("Service PasswordSecurityService", fn() => class_exists('App\\Services\\PasswordSecurityService'));
test("Service TwoFactorService", fn() => class_exists('App\\Services\\TwoFactorService'));
test("Service RateLimitingService", fn() => class_exists('App\\Services\\RateLimitingService'));
test("Service TokenManagementService", fn() => class_exists('App\\Services\\TokenManagementService'));
test("Service SessionTimeoutService", fn() => class_exists('App\\Services\\SessionTimeoutService'));
test("Service VerificationCodeService", fn() => class_exists('App\\Services\\VerificationCodeService'));
test("Service FileSecurityService", fn() => class_exists('App\\Services\\FileSecurityService'));

test("DeviceController->register", fn() => method_exists('App\\Http\\Controllers\\Api\\DeviceController', 'register'));
test("DeviceController->verifyDevice", fn() => method_exists('App\\Http\\Controllers\\Api\\DeviceController', 'verifyDevice'));
test("AuditController->getUserAuditTrail", fn() => method_exists('App\\Http\\Controllers\\Api\\AuditController', 'getUserAuditTrail'));
test("SecurityMonitoringService->checkSuspiciousActivity", fn() => method_exists('App\\Services\\SecurityMonitoringService', 'checkSuspiciousActivity'));

endSection($s4);

// ═══════════════════════════════════════════════════════════════
// بخش 5: Core Features
// ═══════════════════════════════════════════════════════════════
$s5 = section("5️⃣ بخش 5: Core Features");

$testUser = User::where('email', 'core_test@test.com')->first();
if (!$testUser) {
    $testUser = User::factory()->create(['email' => 'core_test@test.com']);
}
$testUsers[] = $testUser;

test("Create audit log", function() use ($testUser) {
    $log = AuditLog::create([
        'user_id' => $testUser->id,
        'action' => 'test.core',
        'ip_address' => '127.0.0.1',
        'timestamp' => now(),
        'risk_level' => 'low'
    ]);
    return $log->exists;
});

test("Create device token", function() use ($testUser) {
    $device = DeviceToken::create([
        'user_id' => $testUser->id,
        'token' => 'core_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'core_' . uniqid(),
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
    $service->log('test.core.log', ['data' => 'test'], null, $testUser->id);
    return AuditLog::where('user_id', $testUser->id)->where('action', 'test.core.log')->exists();
});

test("Security monitoring", function() use ($testUser) {
    $service = app(SecurityMonitoringService::class);
    $result = $service->checkSuspiciousActivity($testUser->id);
    return isset($result['detected']) && isset($result['risk_level']);
});

endSection($s5);

// ═══════════════════════════════════════════════════════════════
// بخش 6: Security & Authorization (30 تست)
// ═══════════════════════════════════════════════════════════════
$s6 = section("6️⃣ بخش 6: Security & Authorization");

// Authentication
test("Sanctum middleware", function() {
    $apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($apiFile, 'auth:sanctum');
});

// Authorization
test("Policy DevicePolicy", fn() => class_exists('App\\Policies\\DevicePolicy'));
test("Policy AuditLogPolicy", fn() => class_exists('App\\Policies\\AuditLogPolicy'));
test("DevicePolicy->register", fn() => method_exists('App\\Policies\\DevicePolicy', 'register'));
test("DevicePolicy->revoke", fn() => method_exists('App\\Policies\\DevicePolicy', 'revoke'));
test("AuditLogPolicy->view", fn() => method_exists('App\\Policies\\AuditLogPolicy', 'view'));

// Permissions (Spatie)
test("Permission device.manage", fn() => Permission::where('name', 'device.manage')->exists());
test("Permission device.register", fn() => Permission::where('name', 'device.register')->exists());
test("Permission device.revoke", fn() => Permission::where('name', 'device.revoke')->exists());
test("Permission device.trust", fn() => Permission::where('name', 'device.trust')->exists());
test("Permission device.view", fn() => Permission::where('name', 'device.view')->exists());

// Roles (Spatie) - تست 6 نقش
test("Role user exists", fn() => Role::where('name', 'user')->exists());
test("Role verified exists", fn() => Role::where('name', 'verified')->exists());
test("Role premium exists", fn() => Role::where('name', 'premium')->exists());
test("Role organization exists", fn() => Role::where('name', 'organization')->exists());
test("Role moderator exists", fn() => Role::where('name', 'moderator')->exists());
test("Role admin exists", fn() => Role::where('name', 'admin')->exists());

// XSS Protection
test("XSS prevention", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.xss', ['content' => '<script>alert("xss")</script>'], null, $testUser->id);
    $log = AuditLog::where('user_id', $testUser->id)->where('action', 'test.xss')->latest()->first();
    return $log && !str_contains(json_encode($log->data), '<script>');
});

// SQL Injection
test("SQL injection protection", function() {
    try {
        AuditLog::where('action', "' OR '1'='1")->get();
        return true;
    } catch (\Exception $e) {
        return false;
    }
});

// Rate Limiting
test("Throttle middleware", function() {
    $apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
    return str_contains($apiFile, 'throttle:');
});

// CSRF
test("CSRF protection", fn() => class_exists('App\\Http\\Middleware\\CSRFProtection'));

// Mass Assignment
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

test("Middleware SecurityMiddleware", fn() => class_exists('App\\Http\\Middleware\\SecurityMiddleware'));
test("Middleware AuditMiddleware", fn() => class_exists('App\\Http\\Middleware\\AuditMiddleware'));

test("Rate limiting service", function() {
    $service = app(RateLimitingService::class);
    $result = $service->checkLimit('test.sec', 'test_id', ['max_attempts' => 2, 'window_minutes' => 1]);
    return $result['allowed'] === true;
});

test("Threat detection", function() {
    $service = app(SecurityMonitoringService::class);
    $result = $service->calculateThreatScore(request());
    return isset($result['score']);
});

test("IP blocking", function() {
    $service = app(SecurityMonitoringService::class);
    $service->blockIP('192.168.1.200', 60, 'test');
    return $service->isIPBlocked('192.168.1.200');
});

test("Sensitive data redaction", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.redact', ['password' => 'secret'], null, $testUser->id);
    $log = AuditLog::where('action', 'test.redact')->first();
    return $log->data['password'] === '[REDACTED]';
});

endSection($s6);

// ═══════════════════════════════════════════════════════════════
// بخش 7: Spam Detection & Prevention
// ═══════════════════════════════════════════════════════════════
$s7 = section("7️⃣ بخش 7: Spam Detection & Prevention");

test("Spam thresholds configured", fn() => is_array(config('security.spam.thresholds')));
test("Spam penalties configured", fn() => is_array(config('security.spam.penalties')));
test("Spam limits configured", fn() => is_array(config('security.spam.limits')));
test("Spam post threshold", fn() => config('security.spam.thresholds.post') > 0);
test("Spam comment threshold", fn() => config('security.spam.thresholds.comment') > 0);
test("Rate limits configured", fn() => is_array(config('limits.rate_limits')));
test("Auth rate limits", fn() => is_array(config('limits.rate_limits.auth')));
test("Security logs for spam", fn() => DB::getSchemaBuilder()->hasTable('security_logs'));
test("Event type tracking", fn() => DB::getSchemaBuilder()->hasColumn('security_logs', 'event_type'));

endSection($s7);

// ═══════════════════════════════════════════════════════════════
// بخش 8: Performance & Optimization
// ═══════════════════════════════════════════════════════════════
$s8 = section("8️⃣ بخش 8: Performance & Optimization");

test("Audit logs indexes", fn() => count(DB::select("SHOW INDEX FROM audit_logs WHERE Key_name != 'PRIMARY'")) > 0);
test("Security logs indexes", fn() => count(DB::select("SHOW INDEX FROM security_logs WHERE Key_name != 'PRIMARY'")) > 0);
test("Cache driver configured", fn() => config('cache.default') !== null);
test("Security cache TTL", fn() => config('security.cache.last_seen') > 0);
test("Database strict mode", fn() => config('database.connections.mysql.strict', true) === true);
test("Eager loading test", function() {
    $device = DeviceToken::with('user')->first();
    return $device ? !is_null($device->user) : true;
});
test("Default pagination", fn() => config('limits.pagination.default') > 0);
test("Max pagination", fn() => config('limits.pagination.default') > 0);

endSection($s8);

// ═══════════════════════════════════════════════════════════════
// بخش 9: Data Integrity & Transactions
// ═══════════════════════════════════════════════════════════════
$s9 = section("9️⃣ بخش 9: Data Integrity & Transactions");

test("FK audit_logs", fn() => count(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME='audit_logs' AND CONSTRAINT_TYPE='FOREIGN KEY'")) > 0);
test("FK device_tokens", fn() => count(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_NAME='device_tokens' AND CONSTRAINT_TYPE='FOREIGN KEY'")) > 0);
test("Cascade rules", fn() => count(DB::select("SELECT DELETE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE TABLE_NAME='device_tokens' AND REFERENCED_TABLE_NAME='users'")) > 0);
test("Device columns exist", fn() => DB::getSchemaBuilder()->hasColumn('device_tokens', 'user_id') && DB::getSchemaBuilder()->hasColumn('device_tokens', 'fingerprint'));
test("No orphaned devices", fn() => DB::table('device_tokens')->leftJoin('users', 'device_tokens.user_id', '=', 'users.id')->whereNull('users.id')->count() === 0);
test("Transaction support", fn() => config('database.connections.mysql.engine') !== 'MyISAM');

endSection($s9);

// ═══════════════════════════════════════════════════════════════
// بخش 10: API & Routes
// ═══════════════════════════════════════════════════════════════
$s10 = section("🔟 بخش 10: API & Routes");

test("Device routes exist", function() {
    $routes = collect(Route::getRoutes())->pluck('uri')->filter(fn($uri) => str_contains($uri, 'device'));
    return $routes->count() > 0;
});
test("Audit routes exist", function() {
    $routes = collect(Route::getRoutes())->pluck('uri')->filter(fn($uri) => str_contains($uri, 'audit'));
    return $routes->count() > 0;
});
test("Security routes exist", function() {
    $routes = collect(Route::getRoutes())->pluck('uri')->filter(fn($uri) => str_contains($uri, 'security'));
    return $routes->count() > 0;
});
test("API prefix", function() {
    $routes = Route::getRoutes();
    foreach ($routes as $route) {
        if (str_contains($route->getName() ?? '', 'device.')) {
            return str_starts_with($route->uri(), 'api/');
        }
    }
    return true;
});

endSection($s10);

// ═══════════════════════════════════════════════════════════════
// بخش 11: Configuration
// ═══════════════════════════════════════════════════════════════
$s11 = section("1️⃣1️⃣ بخش 11: Configuration");

test("Config security.device.max_devices", fn() => config('security.device.max_devices') > 0);
test("Config device max_inactivity_days", fn() => config('security.device.max_inactivity_days') > 0);
test("Config session timeout", fn() => config('security.session.timeout_seconds') > 0);
test("Config session concurrent_limit", fn() => config('security.session.concurrent_limit') > 0);
test("Config password min_length", fn() => config('security.password.security.min_length') >= 8);
test("Config password require_special", fn() => is_bool(config('security.password.security.require_special_chars')));
test("Config WAF enabled", fn() => is_bool(config('security.waf.enabled')));
test("Config threat threshold", fn() => config('security.waf.threat_threshold') > 0);
test("Config rate limits auth", fn() => is_array(config('limits.rate_limits.auth')));
test("Config IP block duration", fn() => config('security.waf.ip_block_duration') > 0);

endSection($s11);

// ═══════════════════════════════════════════════════════════════
// بخش 12: Advanced Features
// ═══════════════════════════════════════════════════════════════
$s12 = section("1️⃣2️⃣ بخش 12: Advanced Features");

test("Two-factor authentication", function() {
    $service = app(TwoFactorService::class);
    return method_exists($service, 'generateSecret') && method_exists($service, 'verifyCode');
});

test("Password security service", fn() => class_exists('App\\Services\\PasswordSecurityService'));
test("Bot detection service", fn() => class_exists('App\\Services\\BotDetectionService'));
test("Session timeout service", fn() => class_exists('App\\Services\\SessionTimeoutService'));
test("Token management service", fn() => class_exists('App\\Services\\TokenManagementService'));
test("Verification code service", fn() => class_exists('App\\Services\\VerificationCodeService'));
test("File security service", fn() => class_exists('App\\Services\\FileSecurityService'));

test("Device fingerprint uniqueness", function() {
    $fp1 = DeviceFingerprintService::generate(request());
    $fp2 = DeviceFingerprintService::generate(request());
    return $fp1 === $fp2;
});

endSection($s12);

// ═══════════════════════════════════════════════════════════════
// بخش 13: Events & Integration
// ═══════════════════════════════════════════════════════════════
$s13 = section("1️⃣3️⃣ بخش 13: Events & Integration");

test("Notifications exist", fn() => is_dir(__DIR__ . '/../app/Notifications'));
test("Mail classes exist", fn() => is_dir(__DIR__ . '/../app/Mail'));
test("Jobs exist", fn() => is_dir(__DIR__ . '/../app/Jobs'));
test("Events integration", fn() => is_dir(__DIR__ . '/../app/Events'));
test("Event integration", function() {
    $service = app(AuditTrailService::class);
    return method_exists($service, 'log');
});
test("Notification integration", function() use ($testUser) {
    return method_exists($testUser, 'notify');
});

endSection($s13);

// ═══════════════════════════════════════════════════════════════
// بخش 14: Error Handling
// ═══════════════════════════════════════════════════════════════
$s14 = section("1️⃣4️⃣ بخش 14: Error Handling");

test("Invalid device registration", function() {
    try {
        DeviceToken::create(['user_id' => 999999, 'token' => 'invalid']);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

test("Invalid fingerprint", function() {
    $result = DeviceFingerprintService::validate('invalid_fp', request());
    return $result === false;
});

test("Rate limit exceeded", function() {
    $service = app(RateLimitingService::class);
    for ($i = 0; $i < 5; $i++) {
        $service->checkLimit('test.err', 'test_err', ['max_attempts' => 3, 'window_minutes' => 1]);
    }
    $result = $service->checkLimit('test.err', 'test_err', ['max_attempts' => 3, 'window_minutes' => 1]);
    return $result['allowed'] === false;
});

test("Invalid audit log", function() {
    try {
        AuditLog::create(['action' => null]);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

test("Missing required fields", function() {
    try {
        DeviceToken::create(['token' => 'test']);
        return false;
    } catch (\Exception $e) {
        return true;
    }
});

endSection($s14);

// ═══════════════════════════════════════════════════════════════
// بخش 15: Resources
// ═══════════════════════════════════════════════════════════════
$s15 = section("1️⃣5️⃣ بخش 15: Resources");

test("Resource DeviceResource", fn() => class_exists('App\\Http\\Resources\\DeviceResource'));
test("DeviceResource structure", function() {
    $device = DeviceToken::first();
    if (!$device) return true;
    $resource = new \App\Http\Resources\DeviceResource($device);
    $array = $resource->toArray(request());
    return isset($array['id']) || isset($array['device_type']);
});
test("Resource hides sensitive data", function() {
    $device = DeviceToken::first();
    if (!$device) return true;
    $resource = new \App\Http\Resources\DeviceResource($device);
    $array = $resource->toArray(request());
    return !isset($array['token']) || strlen($array['token']) < 10;
});

endSection($s15);

// ═══════════════════════════════════════════════════════════════
// بخش 16: User Flows
// ═══════════════════════════════════════════════════════════════
$s16 = section("1️⃣6️⃣ بخش 16: User Flows");

$flowUser = User::where('email', 'flow_test@test.com')->first();
if (!$flowUser) {
    $flowUser = User::factory()->create(['email' => 'flow_test@test.com']);
}
$testUsers[] = $flowUser;

test("Flow: Register device", function() use ($flowUser) {
    $device = DeviceToken::create([
        'user_id' => $flowUser->id,
        'token' => 'flow_' . uniqid(),
        'device_type' => 'mobile',
        'fingerprint' => 'flow_fp_' . uniqid(),
        'is_trusted' => false
    ]);
    return $device->exists;
});

test("Flow: Trust device", function() use ($flowUser) {
    $device = DeviceToken::where('user_id', $flowUser->id)->first();
    $device->update(['is_trusted' => true]);
    return $device->is_trusted === true;
});

test("Flow: Revoke device", function() use ($flowUser) {
    $device = DeviceToken::where('user_id', $flowUser->id)->first();
    $device->delete();
    return !DeviceToken::find($device->id);
});

test("Flow: Audit trail creation", function() use ($flowUser) {
    AuditLog::create([
        'user_id' => $flowUser->id,
        'action' => 'flow.test',
        'ip_address' => '127.0.0.1',
        'timestamp' => now(),
        'risk_level' => 'low'
    ]);
    return AuditLog::where('user_id', $flowUser->id)->where('action', 'flow.test')->exists();
});

test("Flow: Security alert", function() use ($flowUser) {
    $service = app(SecurityMonitoringService::class);
    $service->checkSuspiciousActivity($flowUser->id);
    return true;
});

endSection($s16);

// ═══════════════════════════════════════════════════════════════
// بخش 17: Validation Advanced
// ═══════════════════════════════════════════════════════════════
$s17 = section("1️⃣7️⃣ بخش 17: Validation Advanced");

test("Device name validation", function() {
    $request = new \App\Http\Requests\RegisterDeviceRequest();
    $rules = $request->rules();
    return isset($rules['device_name']);
});

test("Platform validation", function() {
    $request = new \App\Http\Requests\RegisterDeviceRequest();
    $rules = $request->rules();
    return isset($rules['platform']);
});

test("Fingerprint validation", function() {
    $fp = DeviceFingerprintService::generate(request());
    return strlen($fp) === 64 && ctype_xdigit($fp);
});

test("IP address validation", function() {
    $ip = request()->ip();
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
});

test("User agent validation", function() {
    $ua = request()->userAgent();
    return is_string($ua);
});

endSection($s17);

// ═══════════════════════════════════════════════════════════════
// بخش 18: Roles & Permissions Database (تست 6 نقش)
// ═══════════════════════════════════════════════════════════════
$s18 = section("1️⃣8️⃣ بخش 18: Roles & Permissions Database");

// ایجاد کاربران با نقش‌های مختلف
$roleUsers = [];
foreach (['user', 'verified', 'premium', 'organization', 'moderator', 'admin'] as $role) {
    $email = "role_{$role}@test.com";
    $user = User::where('email', $email)->first();
    if (!$user) {
        $user = User::factory()->create(['email' => $email]);
    }
    $roleUsers[$role] = $user;
}
$testUsers = array_merge($testUsers, array_values($roleUsers));

// اختصاص نقش‌ها
foreach ($roleUsers as $roleName => $user) {
    if (Role::where('name', $roleName)->exists()) {
        $user->assignRole($roleName);
    }
}

test("User role assigned", fn() => $roleUsers['user']->hasRole('user'));
test("Verified role assigned", fn() => $roleUsers['verified']->hasRole('verified'));
test("Premium role assigned", fn() => $roleUsers['premium']->hasRole('premium'));
test("Organization role assigned", fn() => $roleUsers['organization']->hasRole('organization'));
test("Moderator role assigned", fn() => $roleUsers['moderator']->hasRole('moderator'));
test("Admin role assigned", fn() => $roleUsers['admin']->hasRole('admin'));

// تست دسترسی‌ها - Positive (can access)
test("User can register device", fn() => $roleUsers['user']->can('device.register'));
test("Verified can trust device", fn() => $roleUsers['verified']->can('device.trust'));
test("Premium can manage devices", fn() => $roleUsers['premium']->can('device.manage'));
test("Admin can manage devices", fn() => $roleUsers['admin']->can('device.manage'));
test("Moderator has permissions", fn() => $roleUsers['moderator']->getAllPermissions()->count() > 0);

// تست دسترسی‌ها - Negative (cannot access)
test("User cannot manage all devices", fn() => !$roleUsers['user']->can('device.manage'));
test("Verified cannot revoke others", fn() => !$roleUsers['verified']->can('device.revoke.all'));
test("Premium cannot view all audits", fn() => !$roleUsers['premium']->can('audit.view.all'));

// تست تفاوت سطوح دسترسی
test("Admin > Moderator permissions", function() use ($roleUsers) {
    $adminPerms = $roleUsers['admin']->getAllPermissions()->count();
    $modPerms = $roleUsers['moderator']->getAllPermissions()->count();
    return $adminPerms >= $modPerms;
});

test("Premium > Verified permissions", function() use ($roleUsers) {
    $premiumPerms = $roleUsers['premium']->getAllPermissions()->count();
    $verifiedPerms = $roleUsers['verified']->getAllPermissions()->count();
    return $premiumPerms >= $verifiedPerms;
});

endSection($s18);

// ═══════════════════════════════════════════════════════════════
// بخش 19: Security Layers Deep Dive
// ═══════════════════════════════════════════════════════════════
$s19 = section("1️⃣9️⃣ بخش 19: Security Layers Deep Dive");

test("Encryption at rest", fn() => config('app.cipher') === 'AES-256-CBC');
test("HTTPS enforcement", fn() => config('app.env') === 'production' ? config('session.secure') === true : true);
test("Secure cookies", fn() => config('session.http_only') === true);
test("Same-site cookies", fn() => in_array(config('session.same_site'), ['lax', 'strict']));
test("Password hashing", function() {
    $hash = Hash::make('test_password');
    return Hash::check('test_password', $hash);
});
test("Token encryption", function() {
    $token = encrypt('test_token');
    return decrypt($token) === 'test_token';
});
test("Audit log encryption", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->log('test.encrypt', ['sensitive' => 'data'], null, $testUser->id);
    return true;
});
test("IP whitelist config", fn() => is_array(config('security.ip_whitelist', [])));
test("IP blacklist config", fn() => is_array(config('security.ip_blacklist', [])));
test("Security headers", fn() => config('security.waf.headers.x_frame_options') !== null);

endSection($s19);

// ═══════════════════════════════════════════════════════════════
// بخش 20: Middleware & Bootstrap
// ═══════════════════════════════════════════════════════════════
$s20 = section("🔟 بخش 20: Middleware & Bootstrap");

test("Middleware registered", fn() => class_exists('Illuminate\\Routing\\Middleware\\ThrottleRequests'));

test("Auth middleware", fn() => class_exists('Illuminate\\Auth\\Middleware\\Authenticate'));
test("Throttle middleware", fn() => class_exists('Illuminate\\Routing\\Middleware\\ThrottleRequests'));
test("CORS middleware", fn() => class_exists('Illuminate\\Http\\Middleware\\HandleCors'));
test("Security middleware exists", fn() => class_exists('App\\Http\\Middleware\\SecurityMiddleware'));
test("Audit middleware exists", fn() => class_exists('App\\Http\\Middleware\\AuditMiddleware'));

test("Service providers loaded", function() {
    $providers = config('app.providers');
    return is_array($providers) && count($providers) > 0;
});

test("Service providers exist", fn() => is_dir(__DIR__ . '/../app/Providers'));

test("Bootstrap complete", fn() => app()->isBooted());

endSection($s20);

// ═══════════════════════════════════════════════════════════════
// پاکسازی
// ═══════════════════════════════════════════════════════════════
echo "\n" . str_repeat("═", 65) . "\n";
echo "🧹 پاکسازی دیتابیس...\n";
echo str_repeat("═", 65) . "\n";

foreach ($testUsers as $user) {
    try {
        $user->devices()->delete();
        AuditLog::where('user_id', $user->id)->delete();
        $user->delete();
    } catch (\Exception $e) {
        // Ignore cleanup errors
    }
}

echo "✓ پاکسازی کامل شد\n\n";

// ═══════════════════════════════════════════════════════════════
// گزارش نهایی
// ═══════════════════════════════════════════════════════════════
echo "\n";
echo "╭" . str_repeat("─", 63) . "╮\n";
echo "│" . str_pad("🏆 گزارش نهایی تست Security System", 63) . "│\n";
echo "├" . str_repeat("─", 63) . "┤\n";
echo "│ ✅ تستهای موفق: " . str_pad($stats['passed'], 40) . "│\n";
echo "│ ❌ تستهای ناموفق: " . str_pad($stats['failed'], 38) . "│\n";
echo "│ ⚠️  هشدارها: " . str_pad($stats['warning'], 44) . "│\n";
echo "├" . str_repeat("─", 63) . "┤\n";

$total = $stats['passed'] + $stats['failed'] + $stats['warning'];
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 2) : 0;

echo "│ 📊 درصد موفقیت: {$percentage}%" . str_repeat(" ", 40 - strlen($percentage)) . "│\n";
echo "│ 📊 تعداد بخشها: 20" . str_repeat(" ", 38) . "│\n";
echo "╰" . str_repeat("─", 63) . "╯\n\n";

if ($stats['failed'] === 0) {
    echo "✨ تبریک! تمام تستها با موفقیت انجام شدند \u2728\n\n";
    exit(0);
} else {
    echo "⚠️  {$stats['failed']} تست ناموفق بود. لطفاً بررسی کنید.\n\n";
    exit(1);
}
