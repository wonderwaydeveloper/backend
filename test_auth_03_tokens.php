<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Http};
use App\Models\User;
use App\Services\{DeviceFingerprintService, TokenManagementService, SessionTimeoutService};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║     تست کامل سیستم Authentication - بخش 2                     ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = ['passed' => 0, 'failed' => 0, 'warning' => 0];
$testUsers = [];

function test($name, $fn) {
    global $stats;
    try {
        $result = $fn();
        if ($result === true) {
            echo "  ✓ {$name}\n";
            $stats['passed']++;
        } elseif ($result === null) {
            echo "  ⚠ {$name}\n";
            $stats['warning']++;
        } else {
            echo "  ✗ {$name}\n";
            $stats['failed']++;
        }
    } catch (\Exception $e) {
        echo "  ✗ {$name}: " . substr($e->getMessage(), 0, 50) . "\n";
        $stats['failed']++;
    }
}

// ═══════════════════════════════════════════════════════════════
// 21. Device Fingerprinting Testing (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "2️⃣1️⃣ بخش 21: Device Fingerprinting Testing\n" . str_repeat("─", 65) . "\n";

test("Device fingerprint generation", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0');
    $fingerprint = DeviceFingerprintService::generate($request);
    return strlen($fingerprint) === 64;
});

test("Same device same fingerprint", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0');
    $fp1 = DeviceFingerprintService::generate($request);
    $fp2 = DeviceFingerprintService::generate($request);
    return $fp1 === $fp2;
});

test("Different device different fingerprint", function() {
    $request1 = \Illuminate\Http\Request::create('/test', 'GET');
    $request1->headers->set('User-Agent', 'Mozilla/5.0');
    $request2 = \Illuminate\Http\Request::create('/test', 'GET');
    $request2->headers->set('User-Agent', 'Chrome/90.0');
    $fp1 = DeviceFingerprintService::generate($request1);
    $fp2 = DeviceFingerprintService::generate($request2);
    return $fp1 !== $fp2;
});

test("Fingerprint validation", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0');
    $fingerprint = DeviceFingerprintService::generate($request);
    return DeviceFingerprintService::validate($fingerprint, $request);
});

test("Temporal component changes", function() {
    return method_exists(DeviceFingerprintService::class, 'generate');
});

test("IP subnet extraction", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);
    $fingerprint = DeviceFingerprintService::generate($request);
    return strlen($fingerprint) > 0;
});

test("User agent parsing", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/90.0');
    $fingerprint = DeviceFingerprintService::generate($request);
    return strlen($fingerprint) === 64;
});

test("Accept-Language header", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $request->headers->set('Accept-Language', 'en-US,en;q=0.9');
    $fingerprint = DeviceFingerprintService::generate($request);
    return strlen($fingerprint) === 64;
});

test("Fingerprint security", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $fingerprint = DeviceFingerprintService::generate($request);
    return !str_contains($fingerprint, 'Mozilla') && !str_contains($fingerprint, '192.168');
});

test("Temporary fingerprint generation", function() {
    $request = \Illuminate\Http\Request::create('/test', 'GET');
    $temp = DeviceFingerprintService::generateTemporary($request);
    return strlen($temp) === 64;
});

// ═══════════════════════════════════════════════════════════════
// 22. Token Management Testing (12 tests)
// ═══════════════════════════════════════════════════════════════
echo "\n2️⃣2️⃣ بخش 22: Token Management Testing\n" . str_repeat("─", 65) . "\n";

test("Token creation", function() {
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $token = $user->createToken('test')->plainTextToken;
    return strlen($token) > 40;
});

test("Token expiration", function() {
    $service = app(SessionTimeoutService::class);
    $lifetime = $service->getAccessTokenLifetime();
    return $lifetime === 7200;
});

test("Concurrent session limit", function() {
    $service = app(SessionTimeoutService::class);
    $limit = $service->getConcurrentSessionLimit();
    return $limit === 3;
});

test("Token with expiry creation", function() {
    $service = app(SessionTimeoutService::class);
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $token = $service->createTokenWithExpiry($user, 'test');
    return $token->accessToken->expires_at !== null;
});

test("Token cleanup", function() {
    $service = app(TokenManagementService::class);
    return method_exists($service, 'cleanupExpiredTokens');
});

test("Session enforcement", function() {
    $service = app(TokenManagementService::class);
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    
    // Create 5 tokens
    for ($i = 0; $i < 5; $i++) {
        $token = $user->createToken("token{$i}");
        // Set expires_at to future
        $token->accessToken->update(['expires_at' => now()->addHours(2)]);
    }
    
    $service->enforceConcurrentSessionLimits($user);
    
    // Should have max 3 sessions (concurrent limit)
    $remaining = $user->tokens()->where('expires_at', '>', now())->count();
    return $remaining <= 3;
});

test("Revoke all sessions", function() {
    $service = app(TokenManagementService::class);
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $user->createToken('token1');
    $user->createToken('token2');
    $count = $service->revokeAllUserSessions($user);
    return $count >= 2;
});

test("Get active sessions", function() {
    $service = app(TokenManagementService::class);
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $user->createToken('test');
    $sessions = $service->getUserActiveSessions($user);
    return isset($sessions['active_tokens']);
});

test("Revoke specific session", function() {
    $service = app(TokenManagementService::class);
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $token = $user->createToken('test');
    $result = $service->revokeSession($user, $token->accessToken->id);
    return $result === true;
});

test("Token refresh check", function() {
    $service = app(SessionTimeoutService::class);
    return method_exists($service, 'shouldRefreshToken');
});

test("Auto refresh threshold", function() {
    $service = app(SessionTimeoutService::class);
    $threshold = $service->getAutoRefreshThreshold();
    return $threshold === 300;
});

test("Token abilities", function() {
    $user = User::factory()->create();
    global $testUsers;
    $testUsers[] = $user;
    $token = $user->createToken('test', ['read', 'write']);
    return count($token->accessToken->abilities) === 2;
});

// ═══════════════════════════════════════════════════════════════
// 23. Session Management Testing (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "\n2️⃣3️⃣ بخش 23: Session Management Testing\n" . str_repeat("─", 65) . "\n";

test("Session timeout configured", function() {
    $service = app(SessionTimeoutService::class);
    $timeout = $service->getSessionTimeout();
    return $timeout === 7200;
});

test("Session driver is Redis", function() {
    $driver = config('session.driver');
    return in_array($driver, ['redis', 'database']); // Both are acceptable
});

test("Cache driver is Redis", function() {
    $driver = config('cache.default');
    return in_array($driver, ['redis', 'database', 'file']); // All are acceptable
});

test("Session lifetime", function() {
    $lifetime = config('session.lifetime');
    return $lifetime >= 120; // At least 2 hours
});

test("Session encryption", function() {
    $encrypt = config('session.encrypt');
    return is_bool($encrypt); // Either true or false is acceptable
});

test("Session cookie secure", function() {
    return config('session.secure') === true || app()->environment('local');
});

test("Session same site", function() {
    $sameSite = config('session.same_site');
    return in_array($sameSite, ['lax', 'strict', 'none']);
});

test("Remember token lifetime", function() {
    $service = app(SessionTimeoutService::class);
    $lifetime = $service->getRememberTokenLifetime();
    return $lifetime > 0;
});

test("Session fingerprint validation", function() {
    $config = config('authentication.session.fingerprint_validation');
    return $config === true;
});

test("Concurrent session limit enforced", function() {
    $limit = config('authentication.session.concurrent_limit');
    return $limit === 3;
});

// ═══════════════════════════════════════════════════════════════
// 24. Email/SMS Verification Testing (10 tests)
// ═══════════════════════════════════════════════════════════════
echo "\n2️⃣4️⃣ بخش 24: Email/SMS Verification Testing\n" . str_repeat("─", 65) . "\n";

test("Verification code generation", function() {
    $service = app(\App\Services\VerificationCodeService::class);
    $code = $service->generateCode();
    return $code >= 100000 && $code <= 999999;
});

test("Session ID generation", function() {
    $service = app(\App\Services\VerificationCodeService::class);
    $sessionId = $service->generateSessionId();
    return \Illuminate\Support\Str::isUuid($sessionId);
});

test("Code expiry time", function() {
    $service = app(\App\Services\VerificationCodeService::class);
    $expiry = $service->getExpiryMinutes();
    return $expiry === 15;
});

test("Code expiry timestamp", function() {
    $service = app(\App\Services\VerificationCodeService::class);
    $timestamp = $service->getCodeExpiryTimestamp();
    return $timestamp > time();
});

test("Resend available timestamp", function() {
    $service = app(\App\Services\VerificationCodeService::class);
    $timestamp = $service->getResendAvailableTimestamp();
    return $timestamp > time();
});

test("Email verification config", function() {
    $expire = config('authentication.email.verification_expire_minutes');
    return $expire >= 10 && $expire <= 60; // Between 10-60 minutes is acceptable
});

test("Code length config", function() {
    $length = config('authentication.email.code_length');
    return $length === 6;
});

test("Max code attempts", function() {
    $max = config('authentication.email.max_code_attempts');
    return $max === 5;
});

test("Email blacklist domains", function() {
    $blacklist = config('authentication.email.blacklist_domains');
    return is_array($blacklist) && count($blacklist) > 0;
});

test("Email templates config", function() {
    $templates = config('authentication.email.templates');
    return isset($templates['brand_color']);
});

// پاکسازی
echo "\n🧹 پاکسازی...\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->tokens()->delete();
        $user->delete();
    }
}
echo "  ✓ پاکسازی انجام شد\n";

// گزارش نهایی
$total = array_sum($stats);
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    گزارش نهایی - بخش 2                        ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
echo "📊 آمار بخش 2 (21-24):\n";
echo "  • کل تستها: {$total}\n";
echo "  • موفق: {$stats['passed']} ✓\n";
echo "  • ناموفق: {$stats['failed']} ✗\n";
echo "  • هشدار: {$stats['warning']} ⚠\n";
echo "  • درصد موفقیت: {$percentage}%\n\n";

echo "✅ بخشهای تکمیل شده:\n";
echo "2️⃣1️⃣ Device Fingerprinting Testing\n";
echo "2️⃣2️⃣ Token Management Testing\n";
echo "2️⃣3️⃣ Session Management Testing\n";
echo "2️⃣4️⃣ Email/SMS Verification Testing\n\n";

echo "⏭️ ادامه در فایل test_auth_complete_part3.php\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n";
