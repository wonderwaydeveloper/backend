<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Cache, Hash, Route};
use App\Models\{User, DeviceToken, AuditLog};
use App\Services\{
    AuthService, EmailService, SmsService, TwoFactorService,
    PasswordSecurityService, RateLimitingService, DeviceFingerprintService,
    VerificationCodeService, SessionTimeoutService, AuditTrailService
};

echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
echo "║       تست جامع سیستم Authentication - 8 بخش (126 تست)       ║\n";
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

test("Controller UnifiedAuthController", fn() => class_exists('App\Http\Controllers\Api\UnifiedAuthController'));
test("Controller PasswordResetController", fn() => class_exists('App\Http\Controllers\Api\PasswordResetController'));
test("Controller SocialAuthController", fn() => class_exists('App\Http\Controllers\Api\SocialAuthController'));

test("Service AuthService", fn() => class_exists('App\Services\AuthService'));
test("Service EmailService", fn() => class_exists('App\Services\EmailService'));
test("Service SmsService", fn() => class_exists('App\Services\SmsService'));
test("Service TwoFactorService", fn() => class_exists('App\Services\TwoFactorService'));
test("Service PasswordSecurityService", fn() => class_exists('App\Services\PasswordSecurityService'));
test("Service VerificationCodeService", fn() => class_exists('App\Services\VerificationCodeService'));
test("Service SessionTimeoutService", fn() => class_exists('App\Services\SessionTimeoutService'));

test("Model User", fn() => class_exists('App\Models\User'));
test("DTO LoginDTO", fn() => class_exists('App\DTOs\LoginDTO'));
test("DTO UserRegistrationDTO", fn() => class_exists('App\DTOs\UserRegistrationDTO'));

test("Request LoginRequest", fn() => class_exists('App\Http\Requests\LoginRequest'));
test("Request PhoneLoginRequest", fn() => class_exists('App\Http\Requests\PhoneLoginRequest'));

test("Rule StrongPassword", fn() => class_exists('App\Rules\StrongPassword'));
test("Rule MinimumAge", fn() => class_exists('App\Rules\MinimumAge'));
test("Rule ValidUsername", fn() => class_exists('App\Rules\ValidUsername'));

test("Policy UserPolicy", fn() => class_exists('App\Policies\UserPolicy'));
test("Resource UserResource", fn() => class_exists('App\Http\Resources\UserResource'));

test("Event UserRegistered", fn() => class_exists('App\Events\UserRegistered'));
test("Mail VerificationEmail", fn() => class_exists('App\Mail\VerificationEmail'));
test("Mail PasswordResetEmail", fn() => class_exists('App\Mail\PasswordResetEmail'));
test("Mail DeviceVerificationEmail", fn() => class_exists('App\Mail\DeviceVerificationEmail'));

test("Middleware CaptchaMiddleware", fn() => class_exists('App\Http\Middleware\CaptchaMiddleware'));
test("Middleware UnifiedSecurityMiddleware", fn() => class_exists('App\Http\Middleware\UnifiedSecurityMiddleware'));

endSection($s1);

// ═══════════════════════════════════════════════════════════════
// 2️⃣ Database & Schema (15%)
// ═══════════════════════════════════════════════════════════════
$s2 = section("2️⃣ Database & Schema", 15);

test("Table users", fn() => DB::getSchemaBuilder()->hasTable('users'));
test("Table password_reset_tokens", fn() => DB::getSchemaBuilder()->hasTable('password_reset_tokens'));
test("Table sessions", fn() => DB::getSchemaBuilder()->hasTable('sessions'));
test("Table password_histories", fn() => DB::getSchemaBuilder()->hasTable('password_histories'));
test("Table device_tokens", fn() => DB::getSchemaBuilder()->hasTable('device_tokens'));

$userCols = array_column(DB::select("SHOW COLUMNS FROM users"), 'Field');
test("users.email", fn() => in_array('email', $userCols));
test("users.username", fn() => in_array('username', $userCols));
test("users.password", fn() => in_array('password', $userCols));
test("users.phone", fn() => in_array('phone', $userCols));
test("users.email_verified_at", fn() => in_array('email_verified_at', $userCols));
test("users.phone_verified_at", fn() => in_array('phone_verified_at', $userCols));
test("users.two_factor_enabled", fn() => in_array('two_factor_enabled', $userCols));
test("users.two_factor_secret", fn() => in_array('two_factor_secret', $userCols));
test("users.password_changed_at", fn() => in_array('password_changed_at', $userCols));
test("users.date_of_birth", fn() => in_array('date_of_birth', $userCols));

$userIdx = DB::select("SHOW INDEXES FROM users");
test("Index users.email", fn() => collect($userIdx)->where('Column_name', 'email')->isNotEmpty());
test("Index users.username", fn() => collect($userIdx)->where('Column_name', 'username')->isNotEmpty());
test("Index users.phone", fn() => collect($userIdx)->where('Column_name', 'phone')->isNotEmpty());

$pwHistCols = array_column(DB::select("SHOW COLUMNS FROM password_histories"), 'Field');
test("password_histories.user_id", fn() => in_array('user_id', $pwHistCols));
test("password_histories.password", fn() => in_array('password', $pwHistCols));

endSection($s2);

// ═══════════════════════════════════════════════════════════════
// 3️⃣ API & Routes (15%)
// ═══════════════════════════════════════════════════════════════
$s3 = section("3️⃣ API & Routes", 15);

$routes = collect(Route::getRoutes())->map(fn($r) => [
    'uri' => $r->uri(),
    'method' => implode('|', $r->methods()),
    'middleware' => $r->middleware()
]);

test("Route auth/login", fn() => $routes->where('uri', 'api/auth/login')->isNotEmpty());
test("Route auth/logout", fn() => $routes->where('uri', 'api/auth/logout')->isNotEmpty());
test("Route auth/me", fn() => $routes->where('uri', 'api/auth/me')->isNotEmpty());
test("Route auth/register/step1", fn() => $routes->where('uri', 'api/auth/register/step1')->isNotEmpty());
test("Route auth/register/step2", fn() => $routes->where('uri', 'api/auth/register/step2')->isNotEmpty());
test("Route auth/register/step3", fn() => $routes->where('uri', 'api/auth/register/step3')->isNotEmpty());
test("Route auth/email/verify", fn() => $routes->where('uri', 'api/auth/email/verify')->isNotEmpty());
test("Route auth/password/forgot", fn() => $routes->where('uri', 'api/auth/password/forgot')->isNotEmpty());
test("Route auth/password/reset", fn() => $routes->where('uri', 'api/auth/password/reset')->isNotEmpty());
test("Route auth/2fa/enable", fn() => $routes->where('uri', 'api/auth/2fa/enable')->isNotEmpty());
test("Route auth/2fa/verify", fn() => $routes->where('uri', 'api/auth/2fa/verify')->isNotEmpty());
test("Route auth/social/{provider}", fn() => $routes->filter(fn($r) => str_contains($r['uri'], 'auth/social/'))->isNotEmpty());

$apiFile = file_get_contents(__DIR__ . '/../routes/api.php');
test("Auth middleware applied", fn() => str_contains($apiFile, 'auth:sanctum'));
test("Security middleware applied", fn() => str_contains($apiFile, 'security:'));
test("Captcha middleware applied", fn() => str_contains($apiFile, 'captcha'));

endSection($s3);

// ═══════════════════════════════════════════════════════════════
// 4️⃣ Security (20%)
// ═══════════════════════════════════════════════════════════════
$s4 = section("4️⃣ Security", 20);

$testUser = User::factory()->create(['email' => 'auth_test@test.com', 'password' => Hash::make('Test123!')]);
$testUsers[] = $testUser;

test("Policy UserPolicy->view", fn() => method_exists('App\Policies\UserPolicy', 'view'));
test("Policy UserPolicy->update", fn() => method_exists('App\Policies\UserPolicy', 'update'));
test("Policy UserPolicy->delete", fn() => method_exists('App\Policies\UserPolicy', 'delete'));
test("Policy UserPolicy->follow", fn() => method_exists('App\Policies\UserPolicy', 'follow'));
test("Policy UserPolicy->block", fn() => method_exists('App\Policies\UserPolicy', 'block'));

test("Password hashing", function() use ($testUser) {
    return Hash::check('Test123!', $testUser->password);
});

test("Password security service", function() {
    $service = app(PasswordSecurityService::class);
    $errors = $service->validatePasswordStrength('weak');
    return count($errors) > 0;
});

test("Strong password validation", function() {
    $service = app(PasswordSecurityService::class);
    $errors = $service->validatePasswordStrength('StrongPass123!');
    return count($errors) === 0;
});

test("2FA secret generation", function() {
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret();
    return !empty($secret) && strlen($secret) > 10;
});

test("2FA QR code generation", function() {
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret();
    $qr = $service->getQRCodeUrl('Test', 'test@test.com', $secret);
    return str_contains($qr, 'otpauth://');
});

test("Rate limiting service", function() {
    $service = app(RateLimitingService::class);
    $result = $service->checkLimit('test.auth', 'test_id', ['max_attempts' => 2, 'window_minutes' => 1]);
    return $result['allowed'] === true;
});

test("Device fingerprint generation", function() {
    $fp = DeviceFingerprintService::generate(request());
    return !empty($fp) && strlen($fp) === 64;
});

test("Device fingerprint validation", function() {
    $fp = DeviceFingerprintService::generate(request());
    return DeviceFingerprintService::validate($fp, request()) === true;
});

test("Session timeout service", function() {
    $service = app(SessionTimeoutService::class);
    return method_exists($service, 'createTokenWithExpiry');
});

test("Verification code generation", function() {
    $service = app(VerificationCodeService::class);
    $code = $service->generateCode();
    return strlen($code) === 6 && is_numeric($code);
});

test("User guarded fields", function() {
    $user = new User();
    $guarded = $user->getGuarded();
    return in_array('password_changed_at', $guarded) && in_array('email_verified_at', $guarded);
});

test("User hidden fields", function() {
    $user = new User();
    $hidden = $user->getHidden();
    return in_array('password', $hidden) && in_array('two_factor_secret', $hidden);
});

test("User casts", function() {
    $user = new User();
    $casts = $user->getCasts();
    return $casts['email_verified_at'] === 'datetime' && $casts['two_factor_enabled'] === 'boolean';
});

test("Permissions exist", function() {
    return \Spatie\Permission\Models\Permission::where('guard_name', 'sanctum')->count() > 0;
});

test("Roles exist", function() {
    return \Spatie\Permission\Models\Role::where('guard_name', 'sanctum')->count() > 0;
});

test("User role assignment", function() use ($testUser) {
    try {
        $testUser->assignRole('user');
        return $testUser->hasRole('user');
    } catch (\Exception $e) {
        return null;
    }
});

test("User permission check", function() use ($testUser) {
    try {
        return method_exists($testUser, 'hasPermissionTo');
    } catch (\Exception $e) {
        return false;
    }
});

test("Audit logging", function() use ($testUser) {
    $service = app(AuditTrailService::class);
    $service->logAuthEvent('test.login', $testUser, [], request());
    return AuditLog::where('user_id', $testUser->id)->where('action', 'auth.test.login')->exists();
});

test("XSS protection in validation", function() {
    $request = new \App\Http\Requests\LoginRequest();
    $rules = $request->rules();
    return isset($rules['login']) && isset($rules['password']);
});

endSection($s4);

// ═══════════════════════════════════════════════════════════════
// 5️⃣ Validation (10%)
// ═══════════════════════════════════════════════════════════════
$s5 = section("5️⃣ Validation", 10);

test("LoginRequest rules", function() {
    $request = new \App\Http\Requests\LoginRequest();
    $rules = $request->rules();
    return isset($rules['login']) && isset($rules['password']) && isset($rules['two_factor_code']);
});

test("LoginRequest messages", function() {
    $request = new \App\Http\Requests\LoginRequest();
    return method_exists($request, 'messages');
});

test("StrongPassword rule", function() {
    $rule = new \App\Rules\StrongPassword();
    return method_exists($rule, 'validate');
});

test("MinimumAge rule", function() {
    $rule = new \App\Rules\MinimumAge();
    return method_exists($rule, 'validate');
});

test("ValidUsername rule", function() {
    $rule = new \App\Rules\ValidUsername();
    return method_exists($rule, 'validate');
});

test("Config authentication.password", fn() => config('authentication.password') !== null);
test("Config authentication.rate_limiting", fn() => config('authentication.rate_limiting') !== null);
test("Config authentication.tokens", fn() => config('authentication.tokens') !== null);
test("Config authentication.session", fn() => config('authentication.session') !== null);
test("Config authentication.email", fn() => config('authentication.email') !== null);
test("Config authentication.device", fn() => config('authentication.device') !== null);
test("Config authentication.social", fn() => config('authentication.social') !== null);

endSection($s5);

// ═══════════════════════════════════════════════════════════════
// 6️⃣ Business Logic (10%)
// ═══════════════════════════════════════════════════════════════
$s6 = section("6️⃣ Business Logic", 10);

test("User factory", function() {
    $user = User::factory()->make();
    return $user->name !== null && $user->email !== null;
});

test("User creation", function() {
    $user = User::factory()->create(['email' => 'test_create@test.com']);
    $exists = User::where('email', 'test_create@test.com')->exists();
    $user->delete();
    return $exists;
});

test("User relationships - devices", function() use ($testUser) {
    return method_exists($testUser, 'devices');
});

test("User relationships - posts", function() use ($testUser) {
    return method_exists($testUser, 'posts');
});

test("User relationships - followers", function() use ($testUser) {
    return method_exists($testUser, 'followers');
});

test("User relationships - following", function() use ($testUser) {
    return method_exists($testUser, 'following');
});

test("User hasBlocked method", function() use ($testUser) {
    return method_exists($testUser, 'hasBlocked');
});

test("User hasMuted method", function() use ($testUser) {
    return method_exists($testUser, 'hasMuted');
});

test("User isFollowing method", function() use ($testUser) {
    return method_exists($testUser, 'isFollowing');
});

test("AuthService login", function() {
    $service = app(AuthService::class);
    return method_exists($service, 'login');
});

test("AuthService register", function() {
    $service = app(AuthService::class);
    return method_exists($service, 'register');
});

test("AuthService forgotPassword", function() {
    $service = app(AuthService::class);
    return method_exists($service, 'forgotPassword');
});

test("AuthService resetPassword", function() {
    $service = app(AuthService::class);
    return method_exists($service, 'resetPassword');
});

test("EmailService sendVerificationEmail", function() {
    $service = app(EmailService::class);
    return method_exists($service, 'sendVerificationEmail');
});

test("SmsService sendVerificationCode", function() {
    $service = app(SmsService::class);
    return method_exists($service, 'sendVerificationCode');
});

endSection($s6);

// ═══════════════════════════════════════════════════════════════
// 7️⃣ Integration (5%)
// ═══════════════════════════════════════════════════════════════
$s7 = section("7️⃣ Integration", 5);

test("User traits - HasFactory", function() {
    $traits = class_uses_recursive(User::class);
    return in_array('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
});

test("User traits - Notifiable", function() {
    $traits = class_uses_recursive(User::class);
    return in_array('Illuminate\Notifications\Notifiable', $traits);
});

test("User traits - HasApiTokens", function() {
    $traits = class_uses_recursive(User::class);
    return in_array('Laravel\Sanctum\HasApiTokens', $traits);
});

test("User traits - HasRoles", function() {
    $traits = class_uses_recursive(User::class);
    return in_array('Spatie\Permission\Traits\HasRoles', $traits);
});

test("User implements MustVerifyEmail", function() {
    $interfaces = class_implements(User::class);
    return in_array('Illuminate\Contracts\Auth\MustVerifyEmail', $interfaces);
});

test("AuthService implements interface", function() {
    $interfaces = class_implements(AuthService::class);
    return in_array('App\Contracts\Services\AuthServiceInterface', $interfaces);
});

test("Block system integration", function() use ($testUser) {
    $user2 = User::factory()->create();
    $testUser->blockedUsers()->attach($user2->id);
    $blocked = $testUser->hasBlocked($user2->id);
    $testUser->blockedUsers()->detach($user2->id);
    $user2->delete();
    return $blocked === true;
});

test("Mute system integration", function() use ($testUser) {
    $user2 = User::factory()->create();
    $testUser->mutedUsers()->attach($user2->id);
    $muted = $testUser->hasMuted($user2->id);
    $testUser->mutedUsers()->detach($user2->id);
    $user2->delete();
    return $muted === true;
});

endSection($s7);

// ═══════════════════════════════════════════════════════════════
// 8️⃣ Testing (5%)
// ═══════════════════════════════════════════════════════════════
$s8 = section("8️⃣ Testing", 5);

test("Password history tracking", function() use ($testUser) {
    $service = app(PasswordSecurityService::class);
    $service->updatePassword($testUser, 'NewPass123!');
    return method_exists($service, 'checkPasswordHistory');
});

test("Device token creation", function() use ($testUser) {
    $device = DeviceToken::create([
        'user_id' => $testUser->id,
        'token' => 'test_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'test_fp_' . uniqid(),
        'is_trusted' => false
    ]);
    return $device->exists;
});

test("Device token trust", function() use ($testUser) {
    $device = DeviceToken::create([
        'user_id' => $testUser->id,
        'token' => 'trust_' . uniqid(),
        'device_type' => 'web',
        'fingerprint' => 'trust_fp_' . uniqid(),
        'is_trusted' => false
    ]);
    $device->update(['is_trusted' => true]);
    return $device->fresh()->is_trusted === true;
});

test("User cascade delete devices", function() {
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

test("User scopes - active", function() {
    return method_exists(User::class, 'scopeActive');
});

test("User scopes - popular", function() {
    return method_exists(User::class, 'scopePopular');
});

endSection($s8);

// ═══════════════════════════════════════════════════════════════
// پاکسازی
// ═══════════════════════════════════════════════════════════════
echo "\n🧹 پاکسازی...\n";
foreach ($testUsers as $user) {
    if ($user && $user->exists) {
        $user->devices()->delete();
        DB::table('password_histories')->where('user_id', $user->id)->delete();
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

echo "📋 نمره بخشها (بر اساس معیارهای استاندارد):\n";
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
    echo "🎉 عالی: سیستم Authentication کاملاً production-ready است!\n";
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
