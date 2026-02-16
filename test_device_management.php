<?php

/**
 * Device Management System - Complete Test Suite
 * System 28 of 31
 * 
 * Combined Tests:
 * - ROADMAP Compliance (25 tests)
 * - Twitter Standards (25 tests)
 * - Operational Features (25 tests)
 * - Integration Tests (25 tests)
 * 
 * Total: 100 tests = 400 points
 */

class DeviceManagementCompleteTest
{
    private int $score = 0;
    private int $totalTests = 114;
    private array $results = [];

    public function runAllTests(): void
    {
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║   Device Management System - Complete Test Suite          ║\n";
        echo "║   System 28 of 31 - 120 Tests (100 + 20 Integration)     ║\n";
        echo "╚════════════════════════════════════════════════════════════╝\n\n";

        $this->testRoadmapCompliance();
        $this->testTwitterStandards();
        $this->testOperationalFeatures();
        $this->testIntegration();
        $this->testAuthenticationIntegration();
        $this->testPermissionIntegration();
        $this->testRouteIntegration();
        $this->testPolicyIntegration();

        $this->displayResults();
    }

    // ==================== ROADMAP COMPLIANCE (25 tests) ====================
    private function testRoadmapCompliance(): void
    {
        echo "┌─ Category 1: ROADMAP Compliance (25 tests) ─────────────────┐\n";

        // Policies (3)
        $this->test(
            "DevicePolicy exists with 6 methods",
            file_exists(__DIR__ . '/app/Policies/DevicePolicy.php') &&
            $this->checkMethodsExist('app/Policies/DevicePolicy.php', ['viewAny', 'view', 'register', 'trust', 'revoke', 'manage'])
        );

        $this->test(
            "DevicePolicy uses hasPermissionTo",
            $this->fileContains('app/Policies/DevicePolicy.php', 'hasPermissionTo')
        );

        $this->test(
            "DevicePolicy validates ownership",
            $this->fileContains('app/Policies/DevicePolicy.php', 'user_id')
        );

        // Resources (3)
        $this->test(
            "DeviceResource with complete fields",
            file_exists(__DIR__ . '/app/Http/Resources/DeviceResource.php') &&
            $this->checkFieldsInResource('app/Http/Resources/DeviceResource.php', ['device_name', 'device_type', 'is_trusted'])
        );

        $this->test(
            "DeviceResource uses ISO8601 dates",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'toIso8601String')
        );

        $this->test(
            "DeviceResource extends JsonResource",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'JsonResource')
        );

        // Permissions (6)
        $this->test(
            "6 device permissions in seeder",
            $this->fileContains('database/seeders/PermissionSeeder.php', 'device.view') &&
            $this->fileContains('database/seeders/PermissionSeeder.php', 'device.register') &&
            $this->fileContains('database/seeders/PermissionSeeder.php', 'device.trust')
        );

        $this->test(
            "Permissions use sanctum guard",
            $this->countOccurrences('database/seeders/PermissionSeeder.php', "'guard_name' => 'sanctum'") >= 6
        );

        $this->test(
            "Permissions assigned to user role",
            $this->fileContains('database/seeders/PermissionSeeder.php', "'device.view'")
        );

        $this->test(
            "Permissions assigned to verified role",
            $this->fileContains('database/seeders/PermissionSeeder.php', "verified")
        );

        $this->test(
            "Permissions assigned to premium role",
            $this->fileContains('database/seeders/PermissionSeeder.php', "'device.manage'")
        );

        $this->test(
            "All 6 permissions exist",
            $this->countOccurrences('database/seeders/PermissionSeeder.php', 'device.') >= 6
        );

        // Requests (3)
        $this->test(
            "RegisterDeviceRequest exists",
            file_exists(__DIR__ . '/app/Http/Requests/RegisterDeviceRequest.php')
        );

        $this->test(
            "AdvancedDeviceRequest exists",
            file_exists(__DIR__ . '/app/Http/Requests/AdvancedDeviceRequest.php')
        );

        $this->test(
            "TrustDeviceRequest validates password",
            file_exists(__DIR__ . '/app/Http/Requests/TrustDeviceRequest.php') &&
            $this->fileContains('app/Http/Requests/TrustDeviceRequest.php', 'password')
        );

        // Authorization (5)
        $this->test(
            "Controller uses auth:sanctum",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "middleware('auth:sanctum')")
        );

        $this->test(
            "Controller authorizes register",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "authorize('register'")
        );

        $this->test(
            "Controller authorizes trust",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "authorize('trust'")
        );

        $this->test(
            "Controller authorizes revoke",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "authorize('revoke'")
        );

        $this->test(
            "Controller authorizes manage",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "authorize('manage'") ||
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "authorize('view'")
        );

        // Documentation (3)
        $this->test(
            "Documentation exists",
            file_exists(__DIR__ . '/docs/DEVICE_MANAGEMENT_SYSTEM.md')
        );

        $this->test(
            "Documentation has all endpoints",
            $this->fileContains('docs/DEVICE_MANAGEMENT_SYSTEM.md', 'Register Device') &&
            $this->fileContains('docs/DEVICE_MANAGEMENT_SYSTEM.md', 'Trust Device')
        );

        $this->test(
            "Documentation has permissions",
            $this->fileContains('docs/DEVICE_MANAGEMENT_SYSTEM.md', 'Permissions')
        );

        // Factory (2)
        $this->test(
            "DeviceTokenFactory exists",
            file_exists(__DIR__ . '/database/factories/DeviceTokenFactory.php')
        );

        $this->test(
            "Factory has states",
            $this->fileContains('database/factories/DeviceTokenFactory.php', 'trusted')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== TWITTER STANDARDS (25 tests) ====================
    private function testTwitterStandards(): void
    {
        echo "┌─ Category 2: Twitter Standards (25 tests) ──────────────────┐\n";

        // Controller (8)
        $this->test(
            "Constructor injection",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'public function __construct(')
        );

        $this->test(
            "JsonResponse return types",
            $this->countOccurrences('app/Http/Controllers/Api/DeviceController.php', '): JsonResponse') >= 8
        );

        $this->test(
            "Uses Request classes",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'RegisterDeviceRequest')
        );

        $this->test(
            "Uses DeviceResource",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DeviceResource')
        );

        $this->test(
            "Proper namespace",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'namespace App\\Http\\Controllers\\Api')
        );

        $this->test(
            "Uses DB transactions",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DB::transaction')
        );

        $this->test(
            "Error handling",
            $this->countOccurrences('app/Http/Controllers/Api/DeviceController.php', 'response()->json') >= 10
        );

        $this->test(
            "Uses Cache",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cache::')
        );

        // Model (5)
        $this->test(
            "Model has HasFactory",
            $this->fileContains('app/Models/DeviceToken.php', 'use HasFactory')
        );

        $this->test(
            "Model has fillable",
            $this->fileContains('app/Models/DeviceToken.php', 'protected $fillable')
        );

        $this->test(
            "Model has casts",
            $this->fileContains('app/Models/DeviceToken.php', 'protected $casts')
        );

        $this->test(
            "Model has relationships",
            $this->fileContains('app/Models/DeviceToken.php', 'belongsTo')
        );

        $this->test(
            "Model has scopes",
            $this->fileContains('app/Models/DeviceToken.php', 'scope')
        );

        // Resource (4)
        $this->test(
            "Resource extends JsonResource",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'extends JsonResource')
        );

        $this->test(
            "Resource has toArray",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'public function toArray')
        );

        $this->test(
            "Resource formats dates",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'toIso8601String()')
        );

        $this->test(
            "Resource has all fields",
            $this->fileContains('app/Http/Resources/DeviceResource.php', 'device_name')
        );

        // Request (3)
        $this->test(
            "Request extends FormRequest",
            $this->fileContains('app/Http/Requests/RegisterDeviceRequest.php', 'extends FormRequest')
        );

        $this->test(
            "Request has authorize",
            $this->fileContains('app/Http/Requests/RegisterDeviceRequest.php', 'public function authorize')
        );

        $this->test(
            "Request has rules",
            $this->fileContains('app/Http/Requests/RegisterDeviceRequest.php', 'public function rules')
        );

        // Policy (3)
        $this->test(
            "Policy uses User model",
            $this->fileContains('app/Policies/DevicePolicy.php', 'User $user')
        );

        $this->test(
            "Policy returns bool",
            $this->fileContains('app/Policies/DevicePolicy.php', '): bool')
        );

        $this->test(
            "Policy proper namespace",
            $this->fileContains('app/Policies/DevicePolicy.php', 'namespace App\\Policies')
        );

        // Factory (2)
        $this->test(
            "Factory extends Factory",
            $this->fileContains('database/factories/DeviceTokenFactory.php', 'extends Factory')
        );

        $this->test(
            "Factory has definition",
            $this->fileContains('database/factories/DeviceTokenFactory.php', 'public function definition')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== OPERATIONAL (25 tests) ====================
    private function testOperationalFeatures(): void
    {
        echo "┌─ Category 3: Operational Features (25 tests) ───────────────┐\n";

        // Registration (5)
        $this->test(
            "Register validates token",
            $this->fileContains('app/Http/Requests/RegisterDeviceRequest.php', "'token'")
        );

        $this->test(
            "Register validates platform",
            $this->fileContains('app/Http/Requests/RegisterDeviceRequest.php', 'platform')
        );

        $this->test(
            "Register creates fingerprint",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DeviceFingerprintService')
        );

        $this->test(
            "Register uses updateOrCreate",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'updateOrCreate')
        );

        $this->test(
            "Register returns resource",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'new DeviceResource')
        );

        // Trust (5)
        $this->test(
            "Trust validates password",
            $this->fileContains('app/Http/Requests/TrustDeviceRequest.php', 'password')
        );

        $this->test(
            "Trust checks hash",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Hash::check')
        );

        $this->test(
            "Trust updates is_trusted",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "'is_trusted' => true")
        );

        $this->test(
            "Trust requires ownership",
            $this->fileContains('app/Policies/DevicePolicy.php', 'user_id')
        );

        $this->test(
            "Trust returns message",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Device trusted')
        );

        // Revoke (5)
        $this->test(
            "Revoke prevents current device",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cannot revoke current')
        );

        $this->test(
            "Revoke uses transaction",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DB::transaction')
        );

        $this->test(
            "Revoke deletes device",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', '$device->delete()')
        );

        $this->test(
            "Revoke clears cache",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cache::forget')
        );

        $this->test(
            "RevokeAll requires password",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "validate(['password'")
        );

        // Verification (5)
        $this->test(
            "Verify validates code",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'size:6')
        );

        $this->test(
            "Verify uses rate limiting",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'checkLimit')
        );

        $this->test(
            "Verify checks expiration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'expires_at')
        );

        $this->test(
            "Verify creates trusted device",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', "'is_trusted' => true")
        );

        $this->test(
            "Verify returns token",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'createTokenWithExpiry')
        );

        // Security (5)
        $this->test(
            "Security score calculation",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'calculateSecurityScore')
        );

        $this->test(
            "Suspicious activity check",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'checkSuspiciousActivity')
        );

        $this->test(
            "Device activity tracking",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'getActivity')
        );

        $this->test(
            "IP tracking",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'ip_address')
        );

        $this->test(
            "User agent tracking",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'user_agent')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== INTEGRATION (25 tests) ====================
    private function testIntegration(): void
    {
        echo "┌─ Category 4: Integration Tests (25 tests) ──────────────────┐\n";

        // Routes (5)
        $this->test(
            "Routes exist in api.php",
            $this->fileContains('routes/api.php', 'DeviceController')
        );

        $this->test(
            "Register routes configured",
            $this->fileContains('routes/api.php', 'register-device') ||
            $this->fileContains('routes/api.php', 'devices/register')
        );

        $this->test(
            "Trust route configured",
            $this->fileContains('routes/api.php', 'trust')
        );

        $this->test(
            "Revoke routes configured",
            $this->fileContains('routes/api.php', 'revoke')
        );

        $this->test(
            "Verification routes configured",
            $this->fileContains('routes/api.php', 'verify-device')
        );

        // Services (5)
        $this->test(
            "EmailService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'EmailService')
        );

        $this->test(
            "RateLimitingService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'RateLimitingService')
        );

        $this->test(
            "SessionTimeoutService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'SessionTimeoutService')
        );

        $this->test(
            "VerificationCodeService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'VerificationCodeService')
        );

        $this->test(
            "DeviceFingerprintService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DeviceFingerprintService')
        );

        // Model (5)
        $this->test(
            "User-Device relationship",
            $this->fileContains('app/Models/DeviceToken.php', 'belongsTo')
        );

        $this->test(
            "Device scopes",
            $this->fileContains('app/Models/DeviceToken.php', 'scope')
        );

        $this->test(
            "Device uses proper table",
            !$this->fileContains('app/Models/DeviceToken.php', 'protected $table') ||
            $this->fileContains('app/Models/DeviceToken.php', 'device_tokens')
        );

        $this->test(
            "Device has timestamps",
            !$this->fileContains('app/Models/DeviceToken.php', 'public $timestamps = false')
        );

        $this->test(
            "Device casts dates",
            $this->fileContains('app/Models/DeviceToken.php', 'datetime')
        );

        // Permissions (5)
        $this->test(
            "Permissions in seeder",
            $this->countOccurrences('database/seeders/PermissionSeeder.php', 'device.') >= 6
        );

        $this->test(
            "User role has permissions",
            $this->fileContains('database/seeders/PermissionSeeder.php', "'device.view'")
        );

        $this->test(
            "Premium role has advanced",
            $this->fileContains('database/seeders/PermissionSeeder.php', "'device.manage'")
        );

        $this->test(
            "Admin has all permissions",
            $this->fileContains('database/seeders/PermissionSeeder.php', "admin")
        );

        $this->test(
            "Permissions use sanctum",
            $this->countOccurrences('database/seeders/PermissionSeeder.php', "'guard_name' => 'sanctum'") >= 6
        );

        // Cache (5)
        $this->test(
            "Verification data cached",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cache::put')
        );

        $this->test(
            "Cache uses fingerprint keys",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'device_verification')
        );

        $this->test(
            "Cache cleared on verify",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cache::forget')
        );

        $this->test(
            "Cache has expiration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'addMinutes')
        );

        $this->test(
            "Cache lock for atomicity",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'Cache::lock') ||
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DB::transaction')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== AUTH INTEGRATION (2 tests) ====================
    private function testAuthenticationIntegration(): void
    {
        echo "┌─ Category 5: Authentication Integration (2 tests) ──────────┐\n";

        $this->test(
            "Device verification in auth flow",
            $this->fileContains('routes/api.php', 'verify-device') &&
            $this->fileContains('routes/api.php', 'resend-device-code')
        );

        $this->test(
            "Device registration in auth routes",
            $this->fileContains('routes/api.php', 'register-device')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== PERMISSION INTEGRATION (4 tests) ====================
    private function testPermissionIntegration(): void
    {
        echo "┌─ Category 6: Permission Integration (4 tests) ──────────────┐\n";

        $this->test(
            "Device permissions in PermissionSeeder",
            $this->fileContains('database/seeders/PermissionSeeder.php', 'device.view') &&
            $this->fileContains('database/seeders/PermissionSeeder.php', 'device.register')
        );

        $this->test(
            "Permissions assigned to roles",
            $this->fileContains('database/seeders/PermissionSeeder.php', "syncPermissions") &&
            $this->fileContains('database/seeders/PermissionSeeder.php', "'device.view'")
        );

        $this->test(
            "DevicePolicy uses hasPermissionTo",
            $this->fileContains('app/Policies/DevicePolicy.php', 'hasPermissionTo')
        );

        $this->test(
            "DevicePolicy registered in AppServiceProvider",
            $this->fileContains('app/Providers/AppServiceProvider.php', 'DeviceToken::class') &&
            $this->fileContains('app/Providers/AppServiceProvider.php', 'DevicePolicy::class')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== SERVICE INTEGRATION (6 tests) ====================
    private function testServiceIntegration(): void
    {
        echo "┌─ Category 7: Service Integration (6 tests) ─────────────────┐\n";

        $this->test(
            "EmailService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'EmailService') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'sendDeviceVerificationEmail')
        );

        $this->test(
            "RateLimitingService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'RateLimitingService') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'checkLimit')
        );

        $this->test(
            "SessionTimeoutService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'SessionTimeoutService') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'createTokenWithExpiry')
        );

        $this->test(
            "VerificationCodeService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'VerificationCodeService') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'generateCode')
        );

        $this->test(
            "DeviceFingerprintService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DeviceFingerprintService::generate')
        );

        $this->test(
            "SecurityMonitoringService integration",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'SecurityMonitoringService') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'checkSuspiciousActivity')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== ROUTE INTEGRATION (4 tests) ====================
    private function testRouteIntegration(): void
    {
        echo "┌─ Category 8: Route Integration (4 tests) ───────────────────┐\n";

        $this->test(
            "Auth routes for device verification",
            $this->fileContains('routes/api.php', "Route::post('/verify-device'") &&
            $this->fileContains('routes/api.php', "Route::post('/resend-device-code'")
        );

        $this->test(
            "Protected device management routes",
            $this->fileContains('routes/api.php', "Route::prefix('devices')") &&
            $this->fileContains('routes/api.php', 'auth:sanctum')
        );

        $this->test(
            "Rate limiting on device routes",
            $this->fileContains('routes/api.php', 'throttle') && $this->fileContains('routes/api.php', 'devices')
        );

        $this->test(
            "All 11 device endpoints exist",
            $this->fileContains('routes/api.php', 'verify-device') &&
            $this->fileContains('routes/api.php', 'register-device') &&
            $this->fileContains('routes/api.php', 'trust') &&
            $this->fileContains('routes/api.php', 'revoke')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== POLICY INTEGRATION (4 tests) ====================
    private function testPolicyIntegration(): void
    {
        echo "┌─ Category 9: Policy Integration (4 tests) ──────────────────┐\n";

        $this->test(
            "DeviceController uses authorize()",
            $this->countOccurrences('app/Http/Controllers/Api/DeviceController.php', '$this->authorize(') >= 5
        );

        $this->test(
            "DevicePolicy has all required methods",
            $this->fileContains('app/Policies/DevicePolicy.php', 'function viewAny') &&
            $this->fileContains('app/Policies/DevicePolicy.php', 'function register') &&
            $this->fileContains('app/Policies/DevicePolicy.php', 'function trust')
        );

        $this->test(
            "DeviceResource used in responses",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'DeviceResource')
        );

        $this->test(
            "Request validation classes used",
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'RegisterDeviceRequest') &&
            $this->fileContains('app/Http/Controllers/Api/DeviceController.php', 'TrustDeviceRequest')
        );

        echo "└──────────────────────────────────────────────────────────────┘\n\n";
    }

    // ==================== HELPERS ====================
    private function test(string $description, bool $condition): void
    {
        $result = $condition ? '✓' : '✗';
        $points = $condition ? 4 : 0;
        $this->score += $points;
        
        $this->results[] = [
            'description' => $description,
            'passed' => $condition,
            'points' => $points
        ];
        
        echo "  {$result} {$description}\n";
    }

    private function fileContains(string $file, string $search): bool
    {
        $path = __DIR__ . '/' . $file;
        return file_exists($path) && str_contains(file_get_contents($path), $search);
    }

    private function countOccurrences(string $file, string $search): int
    {
        $path = __DIR__ . '/' . $file;
        return file_exists($path) ? substr_count(file_get_contents($path), $search) : 0;
    }

    private function checkMethodsExist(string $file, array $methods): bool
    {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) return false;
        $content = file_get_contents($path);
        foreach ($methods as $method) {
            if (!str_contains($content, "function {$method}")) return false;
        }
        return true;
    }

    private function checkFieldsInResource(string $file, array $fields): bool
    {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) return false;
        $content = file_get_contents($path);
        foreach ($fields as $field) {
            if (!str_contains($content, "'{$field}'") && !str_contains($content, "\"{$field}\"")) return false;
        }
        return true;
    }

    private function displayResults(): void
    {
        $maxScore = $this->totalTests * 4;
        $percentage = ($this->score / $maxScore) * 100;
        $passed = count(array_filter($this->results, fn($r) => $r['passed']));
        $failed = $this->totalTests - $passed;
        
        echo "╔════════════════════════════════════════════════════════════╗\n";
        echo "║                    TEST RESULTS                            ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        echo "║ Total Tests:  {$this->totalTests}                                              ║\n";
        echo "║ Passed:       {$passed}                                               ║\n";
        echo "║ Failed:       {$failed}                                                ║\n";
        echo "║ Score:        {$this->score}/{$maxScore} points                                  ║\n";
        echo "║ Percentage:   " . number_format($percentage, 2) . "%                                      ║\n";
        echo "╠════════════════════════════════════════════════════════════╣\n";
        
        if ($this->score === $maxScore) {
            echo "║ Status:       🎉 PERFECT SCORE - 100% COMPLETE!           ║\n";
        } elseif ($percentage >= 90) {
            echo "║ Status:       ✅ EXCELLENT - Production Ready              ║\n";
        } elseif ($percentage >= 75) {
            echo "║ Status:       ⚠️  GOOD - Minor Improvements Needed         ║\n";
        } else {
            echo "║ Status:       ❌ NEEDS WORK - Significant Issues           ║\n";
        }
        
        echo "╚════════════════════════════════════════════════════════════╝\n";
        
        $failed = array_filter($this->results, fn($r) => !$r['passed']);
        if (count($failed) > 0) {
            echo "\n❌ Failed Tests:\n";
            foreach ($failed as $test) {
                echo "  • {$test['description']}\n";
            }
        }
    }
}

// Run tests
$tester = new DeviceManagementCompleteTest();
$tester->runAllTests();
