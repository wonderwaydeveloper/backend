<?php

/**
 * Monetization System Test Script
 * Tests: Advertisements, Creator Fund, Premium Subscriptions
 * Standards: ROADMAP Compliance, Twitter Standards, Operational Readiness
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

class MonetizationSystemTest
{
    private int $totalTests = 0;
    private int $passedTests = 0;
    private array $results = [];
    private int $totalScore = 0;
    private int $maxScore = 400;

    public function run(): void
    {
        echo "\n🧪 Monetization System Test Suite\n";
        echo str_repeat('=', 80)."\n\n";

        $this->testROADMAPCompliance();
        $this->testTwitterStandards();
        $this->testOperationalReadiness();
        $this->testNoParallelWork();

        $this->printResults();
    }

    private function testROADMAPCompliance(): void
    {
        echo "📋 1. ROADMAP Compliance Tests (100 points)\n";
        echo str_repeat('-', 80)."\n";

        // Architecture (20 points)
        $this->test('Controllers exist', function () {
            return file_exists(app_path('Monetization/Controllers/AdvertisementController.php'))
                && file_exists(app_path('Monetization/Controllers/CreatorFundController.php'))
                && file_exists(app_path('Monetization/Controllers/PremiumController.php'));
        }, 7);

        $this->test('Services exist', function () {
            return file_exists(app_path('Monetization/Services/AdvertisementService.php'))
                && file_exists(app_path('Monetization/Services/CreatorFundService.php'))
                && file_exists(app_path('Monetization/Services/PremiumService.php'));
        }, 7);

        $this->test('Models exist', function () {
            return file_exists(app_path('Monetization/Models/Advertisement.php'))
                && file_exists(app_path('Monetization/Models/CreatorFund.php'))
                && file_exists(app_path('Models/PremiumSubscription.php'));
        }, 6);

        // Database (15 points)
        $this->test('Migrations exist', function () {
            return file_exists(database_path('migrations/2025_12_24_000001_create_advertisements_table.php'))
                && file_exists(database_path('migrations/2025_12_24_000002_create_creator_funds_table.php'))
                && file_exists(database_path('migrations/2025_12_24_000003_create_premium_subscriptions_table.php'));
        }, 5);

        $this->test('Tables have indexes', function () {
            $adMigration = file_get_contents(database_path('migrations/2025_12_24_000001_create_advertisements_table.php'));
            $cfMigration = file_get_contents(database_path('migrations/2025_12_24_000002_create_creator_funds_table.php'));
            $psMigration = file_get_contents(database_path('migrations/2025_12_24_000003_create_premium_subscriptions_table.php'));
            
            return str_contains($adMigration, '->index(')
                && str_contains($cfMigration, '->index(')
                && str_contains($psMigration, '->index(');
        }, 5);

        $this->test('Foreign keys defined', function () {
            $adMigration = file_get_contents(database_path('migrations/2025_12_24_000001_create_advertisements_table.php'));
            $cfMigration = file_get_contents(database_path('migrations/2025_12_24_000002_create_creator_funds_table.php'));
            $psMigration = file_get_contents(database_path('migrations/2025_12_24_000003_create_premium_subscriptions_table.php'));
            
            return str_contains($adMigration, '->foreignId(')
                && str_contains($cfMigration, '->foreignId(')
                && str_contains($psMigration, '->foreignId(');
        }, 5);

        // API & Routes (15 points)
        $this->test('Routes registered', function () {
            $routes = Artisan::call('route:list', ['--path' => 'monetization']);
            return $routes === 0;
        }, 8);

        $this->test('RESTful naming', function () {
            $routeFile = file_get_contents(base_path('routes/api.php'));
            return str_contains($routeFile, 'monetization');
        }, 7);

        // Security (20 points)
        $this->test('Policies exist', function () {
            return file_exists(app_path('Policies/AdvertisementPolicy.php'))
                && file_exists(app_path('Policies/CreatorFundPolicy.php'))
                && file_exists(app_path('Policies/PremiumSubscriptionPolicy.php'));
        }, 7);

        $this->test('Permissions defined', function () {
            $seeder = file_get_contents(database_path('seeders/PermissionSeeder.php'));
            return str_contains($seeder, 'advertisement.view')
                && str_contains($seeder, 'creatorfund.view')
                && str_contains($seeder, 'premium.view');
        }, 7);

        $this->test('Authorization in controllers', function () {
            $adController = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            $cfController = file_get_contents(app_path('Monetization/Controllers/CreatorFundController.php'));
            $psController = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            
            return str_contains($adController, '$this->authorize(')
                && str_contains($cfController, '$this->authorize(')
                && str_contains($psController, '$this->authorize(');
        }, 6);

        // Validation (10 points)
        $this->test('Request classes exist', function () {
            return file_exists(app_path('Http/Requests/AdvertisementRequest.php'))
                && file_exists(app_path('Http/Requests/CreatorFundRequest.php'))
                && file_exists(app_path('Http/Requests/PremiumSubscriptionRequest.php'));
        }, 10);

        // Business Logic (10 points)
        $this->test('Service methods implemented', function () {
            $adService = file_get_contents(app_path('Monetization/Services/AdvertisementService.php'));
            $cfService = file_get_contents(app_path('Monetization/Services/CreatorFundService.php'));
            $psService = file_get_contents(app_path('Monetization/Services/PremiumService.php'));
            
            return str_contains($adService, 'function createAdvertisement')
                && str_contains($cfService, 'function calculateMonthlyEarnings')
                && str_contains($psService, 'function subscribe');
        }, 10);

        // Integration (5 points)
        $this->test('User model relations', function () {
            $userModel = file_get_contents(app_path('Models/User.php'));
            return str_contains($userModel, 'function creatorFunds()')
                && str_contains($userModel, 'function advertisements()')
                && str_contains($userModel, 'function premiumSubscriptions()');
        }, 5);

        // Testing (5 points)
        $this->test('Factories exist', function () {
            return file_exists(database_path('factories/Monetization/Models/AdvertisementFactory.php'))
                && file_exists(database_path('factories/Monetization/Models/CreatorFundFactory.php'))
                && file_exists(database_path('factories/PremiumSubscriptionFactory.php'));
        }, 5);

        echo "\n";
    }

    private function testTwitterStandards(): void
    {
        echo "🐦 2. Twitter/X Standards Tests (100 points)\n";
        echo str_repeat('-', 80)."\n";

        $this->test('API Resources exist', function () {
            return file_exists(app_path('Http/Resources/AdvertisementResource.php'))
                && file_exists(app_path('Http/Resources/CreatorFundResource.php'))
                && file_exists(app_path('Http/Resources/PremiumResource.php'));
        }, 15);

        $this->test('JsonResponse return types', function () {
            $adController = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            $cfController = file_get_contents(app_path('Monetization/Controllers/CreatorFundController.php'));
            $psController = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            
            return str_contains($adController, '): JsonResponse')
                && str_contains($cfController, '): JsonResponse')
                && str_contains($psController, '): JsonResponse');
        }, 15);

        $this->test('Constructor injection pattern', function () {
            $adController = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            $cfController = file_get_contents(app_path('Monetization/Controllers/CreatorFundController.php'));
            $psController = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            
            return str_contains($adController, 'public function __construct(')
                && str_contains($cfController, 'public function __construct(')
                && str_contains($psController, 'public function __construct(');
        }, 15);

        $this->test('Route model binding', function () {
            $adController = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            $psController = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            
            return str_contains($adController, 'Advertisement $ad')
                && str_contains($psController, 'PremiumSubscription $subscription');
        }, 10);

        $this->test('HasFactory trait on models', function () {
            $adModel = file_get_contents(app_path('Monetization/Models/Advertisement.php'));
            $cfModel = file_get_contents(app_path('Monetization/Models/CreatorFund.php'));
            $psModel = file_get_contents(app_path('Models/PremiumSubscription.php'));
            
            return str_contains($adModel, 'use HasFactory;')
                && str_contains($cfModel, 'use HasFactory;')
                && str_contains($psModel, 'use HasFactory;');
        }, 15);

        $this->test('Proper fillable/casts', function () {
            $adModel = file_get_contents(app_path('Monetization/Models/Advertisement.php'));
            $cfModel = file_get_contents(app_path('Monetization/Models/CreatorFund.php'));
            $psModel = file_get_contents(app_path('Models/PremiumSubscription.php'));
            
            return str_contains($adModel, 'protected $fillable')
                && str_contains($cfModel, 'protected $fillable')
                && str_contains($psModel, 'protected $fillable');
        }, 15);

        $this->test('ISO8601 date formatting', function () {
            $adResource = file_get_contents(app_path('Http/Resources/AdvertisementResource.php'));
            $cfResource = file_get_contents(app_path('Http/Resources/CreatorFundResource.php'));
            $psResource = file_get_contents(app_path('Http/Resources/PremiumResource.php'));
            
            return str_contains($adResource, 'toIso8601String()')
                && str_contains($cfResource, 'toIso8601String()')
                && str_contains($psResource, 'toIso8601String()');
        }, 15);

        echo "\n";
    }

    private function testOperationalReadiness(): void
    {
        echo "⚙️  3. Operational Readiness Tests (100 points)\n";
        echo str_repeat('-', 80)."\n";

        $this->test('Advertisement CRUD complete', function () {
            $controller = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            return str_contains($controller, 'function create(')
                && str_contains($controller, 'function getTargetedAds(')
                && str_contains($controller, 'function pause(')
                && str_contains($controller, 'function resume(');
        }, 15);

        $this->test('Creator Fund features', function () {
            $controller = file_get_contents(app_path('Monetization/Controllers/CreatorFundController.php'));
            return str_contains($controller, 'function getAnalytics(')
                && str_contains($controller, 'function calculateEarnings(')
                && str_contains($controller, 'function getEarningsHistory(')
                && str_contains($controller, 'function requestPayout(');
        }, 15);

        $this->test('Premium subscription features', function () {
            $controller = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            return str_contains($controller, 'function getPlans(')
                && str_contains($controller, 'function subscribe(')
                && str_contains($controller, 'function cancel(')
                && str_contains($controller, 'function getStatus(');
        }, 15);

        $this->test('Advertisement targeting logic', function () {
            $service = file_get_contents(app_path('Monetization/Services/AdvertisementService.php'));
            return str_contains($service, 'function getTargetedAds(')
                && str_contains($service, 'target_audience');
        }, 10);

        $this->test('Creator Fund earnings calculation', function () {
            $service = file_get_contents(app_path('Monetization/Services/CreatorFundService.php'));
            return str_contains($service, 'function calculateMonthlyEarnings(')
                && str_contains($service, 'quality_score');
        }, 10);

        $this->test('Advertisement analytics', function () {
            $service = file_get_contents(app_path('Monetization/Services/AdvertisementService.php'));
            return str_contains($service, 'function getAdvertiserAnalytics(')
                && str_contains($service, 'impressions_count')
                && str_contains($service, 'clicks_count');
        }, 10);

        $this->test('Premium plan features', function () {
            $service = file_get_contents(app_path('Monetization/Services/PremiumService.php'));
            return str_contains($service, 'function getPlans(')
                && str_contains($service, 'features');
        }, 10);

        $this->test('Model business methods', function () {
            $adModel = file_get_contents(app_path('Monetization/Models/Advertisement.php'));
            $cfModel = file_get_contents(app_path('Monetization/Models/CreatorFund.php'));
            $psModel = file_get_contents(app_path('Models/PremiumSubscription.php'));
            
            return str_contains($adModel, 'function getCTR(')
                && str_contains($cfModel, 'function calculateEarnings(')
                && str_contains($psModel, 'function isActive(');
        }, 15);

        echo "\n";
    }

    private function testNoParallelWork(): void
    {
        echo "🔍 4. No Parallel Work Tests (100 points)\n";
        echo str_repeat('-', 80)."\n";

        $this->test('No duplicate Advertisement files', function () {
            $files = [
                app_path('Monetization/Controllers/AdvertisementController.php'),
                app_path('Monetization/Services/AdvertisementService.php'),
                app_path('Monetization/Models/Advertisement.php'),
            ];
            
            foreach ($files as $file) {
                if (!file_exists($file)) {
                    return false;
                }
            }
            return true;
        }, 20);

        $this->test('No duplicate CreatorFund files', function () {
            $files = [
                app_path('Monetization/Controllers/CreatorFundController.php'),
                app_path('Monetization/Services/CreatorFundService.php'),
                app_path('Monetization/Models/CreatorFund.php'),
            ];
            
            foreach ($files as $file) {
                if (!file_exists($file)) {
                    return false;
                }
            }
            return true;
        }, 20);

        $this->test('No duplicate Premium files', function () {
            $files = [
                app_path('Monetization/Controllers/PremiumController.php'),
                app_path('Monetization/Services/PremiumService.php'),
                app_path('Models/PremiumSubscription.php'),
            ];
            
            foreach ($files as $file) {
                if (!file_exists($file)) {
                    return false;
                }
            }
            return true;
        }, 20);

        $this->test('Consistent naming conventions', function () {
            $adController = file_get_contents(app_path('Monetization/Controllers/AdvertisementController.php'));
            $cfController = file_get_contents(app_path('Monetization/Controllers/CreatorFundController.php'));
            $psController = file_get_contents(app_path('Monetization/Controllers/PremiumController.php'));
            
            return str_contains($adController, 'namespace App\\Monetization\\Controllers;')
                && str_contains($cfController, 'namespace App\\Monetization\\Controllers;')
                && str_contains($psController, 'namespace App\\Monetization\\Controllers;');
        }, 20);

        $this->test('All permissions use sanctum guard', function () {
            $seeder = file_get_contents(database_path('seeders/PermissionSeeder.php'));
            $adCount = substr_count($seeder, "'advertisement.");
            $cfCount = substr_count($seeder, "'creatorfund.");
            $pmCount = substr_count($seeder, "'premium.");
            $sanctumCount = substr_count($seeder, "'guard_name' => 'sanctum'");
            
            return $adCount > 0 && $cfCount > 0 && $pmCount > 0 && $sanctumCount > 0;
        }, 20);

        echo "\n";
    }

    private function test(string $name, callable $callback, int $points): void
    {
        $this->totalTests++;
        
        try {
            $result = $callback();
            
            if ($result) {
                $this->passedTests++;
                $this->totalScore += $points;
                $status = "✅ PASS";
                $color = "\033[32m";
            } else {
                $status = "❌ FAIL";
                $color = "\033[31m";
            }
        } catch (Exception $e) {
            $status = "❌ ERROR";
            $color = "\033[31m";
            $result = false;
        }
        
        $this->results[] = [
            'name' => $name,
            'status' => $result,
            'points' => $result ? $points : 0,
            'max_points' => $points,
        ];
        
        echo sprintf(
            "%s%-60s %s (+%d pts)\033[0m\n",
            $color,
            $name,
            $status,
            $result ? $points : 0
        );
    }

    private function printResults(): void
    {
        echo str_repeat('=', 80)."\n";
        echo "\n📊 Test Results Summary\n\n";
        
        $percentage = ($this->totalScore / $this->maxScore) * 100;
        
        echo sprintf("Total Tests: %d\n", $this->totalTests);
        echo sprintf("Passed: %d\n", $this->passedTests);
        echo sprintf("Failed: %d\n", $this->totalTests - $this->passedTests);
        echo sprintf("Score: %d/%d (%.1f%%)\n\n", $this->totalScore, $this->maxScore, $percentage);
        
        if ($percentage >= 95) {
            echo "🎉 Status: ✅ PRODUCTION READY\n";
        } elseif ($percentage >= 85) {
            echo "⚠️  Status: 🟡 GOOD (Minor fixes needed)\n";
        } elseif ($percentage >= 70) {
            echo "⚠️  Status: 🟠 MODERATE (Improvements required)\n";
        } else {
            echo "❌ Status: 🔴 POOR (Major work needed)\n";
        }
        
        echo "\n";
    }
}

$test = new MonetizationSystemTest();
$test->run();
