<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\{User, Post};
use Spatie\Permission\Models\{Role, Permission};
use Illuminate\Support\Facades\{Hash, Gate, Route, Config};

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   🔐 تست کامل سیستم Authorization - Clevlance Backend\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$stats = ['passed' => 0, 'failed' => 0, 'warnings' => 0];

function test($condition, $message, &$stats) {
    if ($condition) {
        echo "  ✅ $message\n";
        $stats['passed']++;
    } else {
        echo "  ❌ $message\n";
        $stats['failed']++;
    }
}

// ═══════════════════════════════════════════════════════════════
// بخش 1: Database Schema & Seeders (8 تست)
// ═══════════════════════════════════════════════════════════════
echo "📋 بخش 1: Database Schema & Seeders\n";
echo str_repeat("─", 63) . "\n";

test(\DB::getSchemaBuilder()->hasTable('roles'), 'جدول roles', $stats);
test(\DB::getSchemaBuilder()->hasTable('permissions'), 'جدول permissions', $stats);
test(Permission::where('guard_name', 'sanctum')->count() === 90, '90 دسترسی', $stats);
test(Role::where('guard_name', 'sanctum')->count() === 6, '6 نقش', $stats);
test(Permission::where('guard_name', '!=', 'sanctum')->count() === 0, 'همه permissions با guard sanctum', $stats);
test(Role::where('guard_name', '!=', 'sanctum')->count() === 0, 'همه roles با guard sanctum', $stats);

$allPerms = Permission::where('guard_name', 'sanctum')->pluck('name')->toArray();
test(count($allPerms) === count(array_unique($allPerms)), 'بدون دسترسی تکراری', $stats);

$expectedRoles = ['admin', 'moderator', 'organization', 'premium', 'user', 'verified'];
$actualRoles = Role::where('guard_name', 'sanctum')->pluck('name')->toArray();
sort($actualRoles);
test($actualRoles === $expectedRoles, 'نقشها: user, verified, premium, organization, moderator, admin', $stats);

// ═══════════════════════════════════════════════════════════════
// بخش 2: Role Permission Distribution (6 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 2: توزیع دسترسیها\n";
echo str_repeat("─", 63) . "\n";

$rolePermCounts = [
    'user' => 27, 'verified' => 44, 'premium' => 63,
    'organization' => 62, 'moderator' => 48, 'admin' => 90
];

foreach ($rolePermCounts as $roleName => $expectedCount) {
    $role = Role::findByName($roleName, 'sanctum');
    $actualCount = $role->permissions->count();
    test($actualCount === $expectedCount, "$roleName: $actualCount دسترسی", $stats);
}

// ═══════════════════════════════════════════════════════════════
// بخش 3: Role Hierarchy (2 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 3: سلسله مراتب نقشها\n";
echo str_repeat("─", 63) . "\n";

$user = Role::findByName('user', 'sanctum')->permissions->count();
$verified = Role::findByName('verified', 'sanctum')->permissions->count();
$premium = Role::findByName('premium', 'sanctum')->permissions->count();

test($verified > $user, "Verified ($verified) > User ($user)", $stats);
test($premium > $verified, "Premium ($premium) > Verified ($verified)", $stats);

// ═══════════════════════════════════════════════════════════════
// بخش 4: Policies & Controllers (10 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 4: Policies & Controllers\n";
echo str_repeat("─", 63) . "\n";

$policies = ['PostPolicy', 'CommentPolicy', 'SpacePolicy', 'ABTestPolicy', 'AdvertisementPolicy'];
foreach ($policies as $policy) {
    test(class_exists("App\\Policies\\$policy"), "$policy وجود دارد", $stats);
}

$controllers = ['ABTestController', 'PerformanceController', 'MonitoringController', 'AutoScalingController'];
foreach ($controllers as $controller) {
    $exists = class_exists("App\\Http\\Controllers\\Api\\$controller");
    test($exists, "$controller وجود دارد", $stats);
}

test(class_exists("App\\Monetization\\Controllers\\AdvertisementController"), 'AdvertisementController وجود دارد', $stats);

// ═══════════════════════════════════════════════════════════════
// بخش 5: Middleware & Routes (5 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 5: Middleware & Routes\n";
echo str_repeat("─", 63) . "\n";

$middlewareAliases = app('router')->getMiddleware();
test(isset($middlewareAliases['role']), "Middleware 'role' ثبت شده", $stats);
test(isset($middlewareAliases['permission']), "Middleware 'permission' ثبت شده", $stats);

$routes = Route::getRoutes();
$criticalRoutes = [
    ['uri' => 'api/performance/dashboard', 'mw' => 'role:admin'],
    ['uri' => 'api/ab-tests', 'mw' => 'role:admin'],
    ['uri' => 'api/monetization/ads', 'mw' => 'permission:advertisement'],
];

foreach ($criticalRoutes as $check) {
    $found = false;
    foreach ($routes as $route) {
        if (str_contains($route->uri(), $check['uri'])) {
            $middleware = $route->gatherMiddleware();
            foreach ($middleware as $m) {
                if (str_contains($m, $check['mw'])) {
                    $found = true;
                    break 2;
                }
            }
        }
    }
    test($found, "{$check['uri']} دارای middleware", $stats);
}

// ═══════════════════════════════════════════════════════════════
// بخش 6: Runtime Permission Tests (9 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 6: تست Runtime\n";
echo str_repeat("─", 63) . "\n";

User::where('email', 'LIKE', 'test_%@clevlance.test')->delete();

$testUser = User::create([
    'name' => 'Test User', 'username' => 'testuser_' . time(),
    'email' => 'test_user@clevlance.test', 'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$testUser->assignRole('user');

$testOrg = User::create([
    'name' => 'Test Org', 'username' => 'testorg_' . time(),
    'email' => 'test_org@clevlance.test', 'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$testOrg->assignRole('organization');

$testAdmin = User::create([
    'name' => 'Test Admin', 'username' => 'testadmin_' . time(),
    'email' => 'test_admin@clevlance.test', 'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$testAdmin->assignRole('admin');

test($testUser->hasPermissionTo('post.create'), 'User: post.create', $stats);
test(!$testUser->hasPermissionTo('list.create'), 'User: !list.create', $stats);
test(!$testUser->hasPermissionTo('performance.view'), 'User: !performance.view', $stats);

test($testOrg->hasPermissionTo('advertisement.create'), 'Organization: advertisement.create', $stats);
test(!$testOrg->hasPermissionTo('abtest.view'), 'Organization: !abtest.view', $stats);

test($testAdmin->hasPermissionTo('performance.view'), 'Admin: performance.view', $stats);
test($testAdmin->hasPermissionTo('abtest.create'), 'Admin: abtest.create', $stats);
test($testAdmin->getAllPermissions()->count() === 90, 'Admin: همه 90 دسترسی', $stats);

// Policy Test
$testPost = Post::create(['user_id' => $testUser->id, 'content' => 'Test', 'visibility' => 'public']);
test(Gate::forUser($testUser)->allows('update', $testPost), 'User میتواند پست خود را ویرایش کند', $stats);

// Cleanup
Post::where('user_id', $testUser->id)->delete();
User::where('email', 'LIKE', 'test_%@clevlance.test')->delete();

// ═══════════════════════════════════════════════════════════════
// بخش 7: Twitter API v2 Standards (10 تست)
// ═══════════════════════════════════════════════════════════════
echo "\n📋 بخش 7: استانداردهای Twitter API v2\n";
echo str_repeat("─", 63) . "\n";

test(Config::get('auth.defaults.guard') === 'sanctum', 'Default guard: sanctum (OAuth 2.0)', $stats);

$requiredCategories = [
    'post' => ['create', 'edit.own', 'delete.own'],
    'space' => ['create', 'join', 'leave'],
    'list' => ['create', 'edit.own', 'delete.own'],
];

$allGood = true;
foreach ($requiredCategories as $category => $actions) {
    foreach ($actions as $action) {
        if (!Permission::where('name', "$category.$action")->exists()) {
            $allGood = false;
            break 2;
        }
    }
}
test($allGood, 'Granular permissions (post, space, list)', $stats);

$orgRole = Role::findByName('organization', 'sanctum');
$orgPerms = $orgRole->permissions->pluck('name')->toArray();
test(in_array('advertisement.create', $orgPerms), 'Organization: advertisement features', $stats);

$premiumRole = Role::findByName('premium', 'sanctum');
$premiumPerms = $premiumRole->permissions->pluck('name')->toArray();
test(in_array('analytics.view', $premiumPerms), 'Premium: analytics.view', $stats);
test(in_array('media.upload.hd', $premiumPerms), 'Premium: media.upload.hd', $stats);
test(in_array('space.create', $premiumPerms), 'Premium: space.create', $stats);

$verifiedRole = Role::findByName('verified', 'sanctum');
$verifiedPerms = $verifiedRole->permissions->pluck('name')->toArray();
test(in_array('creatorfund.view', $verifiedPerms), 'Verified: creatorfund.view', $stats);
test(in_array('list.create', $verifiedPerms), 'Verified: list.create', $stats);

$modRole = Role::findByName('moderator', 'sanctum');
$modPerms = $modRole->permissions->pluck('name')->toArray();
test(in_array('user.ban', $modPerms) && in_array('content.moderate', $modPerms), 'Moderator: moderation powers', $stats);

// ═══════════════════════════════════════════════════════════════
// گزارش نهایی
// ═══════════════════════════════════════════════════════════════
echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   📊 گزارش نهایی\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  ✅ موفق: {$stats['passed']}\n";
echo "  ❌ ناموفق: {$stats['failed']}\n";
echo "  ⚠️  هشدار: {$stats['warnings']}\n";

$total = $stats['passed'] + $stats['failed'] + $stats['warnings'];
$percentage = $total > 0 ? round(($stats['passed'] / $total) * 100, 2) : 0;
echo "  📈 درصد موفقیت: $percentage%\n";
echo "\n";
echo "  بخشهای تست شده:\n";
echo "  1️⃣  Database Schema & Seeders (8 تست)\n";
echo "  2️⃣  Role Permission Distribution (6 تست)\n";
echo "  3️⃣  Role Hierarchy (2 تست)\n";
echo "  4️⃣  Policies & Controllers (10 تست)\n";
echo "  5️⃣  Middleware & Routes (5 تست)\n";
echo "  6️⃣  Runtime Permission Tests (9 تست)\n";
echo "  7️⃣  Twitter API v2 Standards (10 تست)\n";
echo "  \n";
echo "  📦 جمع کل: $total تست\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

if ($stats['failed'] === 0 && $stats['warnings'] === 0) {
    echo "🎉 سیستم Authorization کاملاً عملیاتی و استاندارد است!\n";
    echo "✅ آماده Production\n\n";
    exit(0);
} else {
    echo "⚠️  برخی تستها ناموفق بودند.\n\n";
    exit(1);
}
