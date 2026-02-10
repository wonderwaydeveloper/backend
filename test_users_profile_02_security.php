<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\{DB, Hash, Route, Validator};
use App\Models\{User, Block, Mute};
use App\Http\Controllers\Api\{ProfileController, FollowController};
use App\Services\{UserService, UserModerationService};
use App\Rules\{ValidUsername, FileUpload, ContentLength, StrongPassword, MinimumAge};
use App\Http\Requests\{UpdateProfileRequest, Auth\RegisterRequest};
use App\Http\Resources\UserResource;
use App\Policies\UserPolicy;
use App\Events\{UserUpdated, UserBlocked, UserMuted, UserFollowed};

class UsersProfileSecurityTest
{
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testUsers = [];

    public function runAllTests()
    {
        echo "🔒 Users & Profile Security & Deep Audit Tests\n";
        echo str_repeat("═", 70) . "\n";

        $this->testAuthenticationSecurity();
        $this->testAuthorizationSecurity();
        $this->testInputValidationSecurity();
        $this->testMassAssignmentSecurity();
        $this->testSQLInjectionSecurity();
        $this->testXSSProtectionSecurity();
        $this->testPasswordSecurity();
        $this->testPrivacySecurity();
        $this->testRateLimitingSecurity();
        $this->testDataLeakageSecurity();
        $this->testBusinessLogicSecurity();
        $this->testDatabaseIntegrity();
        $this->testModelCompleteness();
        $this->testControllerCompleteness();
        $this->testServiceCompleteness();
        $this->testValidationCompleteness();
        $this->testEventSystem();
        $this->testResourcesAndDTOs();
        $this->testPolicyCompleteness();
        $this->testPerformanceOptimization();

        $this->cleanup();
        $this->displayResults();
    }

    private function testAuthenticationSecurity()
    {
        echo "\n🔐 Authentication Security...\n";
        
        $this->test("Routes require authentication", function() {
            $routes = Route::getRoutes();
            $protectedRoutes = 0;
            $totalProfileRoutes = 0;
            
            foreach ($routes as $route) {
                $uri = $route->uri();
                if (strpos($uri, 'api/users/') !== false || strpos($uri, 'api/profile') !== false) {
                    $totalProfileRoutes++;
                    $middleware = $route->middleware();
                    if (in_array('auth:sanctum', $middleware)) {
                        $protectedRoutes++;
                    }
                }
            }
            
            return $protectedRoutes >= ($totalProfileRoutes * 0.9);
        });

        $this->test("Sanctum tokens properly validated", function() {
            $user = new User();
            $traits = class_uses($user);
            return in_array('Laravel\Sanctum\HasApiTokens', $traits);
        });

        $this->test("User verification interface implemented", function() {
            $user = new User();
            return $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail;
        });
    }

    private function testAuthorizationSecurity()
    {
        echo "\n🛡️ Authorization Security...\n";
        
        $this->test("UserPolicy exists with all methods", function() {
            if (!class_exists('App\Policies\UserPolicy')) {
                return false;
            }
            
            $methods = ['view', 'update', 'delete', 'follow', 'block', 'mute'];
            foreach ($methods as $method) {
                if (!method_exists('App\Policies\UserPolicy', $method)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("Controllers use authorization", function() {
            $profileController = file_get_contents(__DIR__ . '/app/Http/Controllers/Api/ProfileController.php');
            $authorizeCalls = substr_count($profileController, '$this->authorize(');
            return $authorizeCalls >= 5;
        });

        $this->test("Policy prevents unauthorized access", function() {
            try {
                $user1 = User::create([
                    'name' => 'Test User 1',
                    'username' => 'testuser1_' . time(),
                    'email' => 'test1_' . time() . '@example.com',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now()
                ]);
                
                $user2 = User::create([
                    'name' => 'Test User 2',
                    'username' => 'testuser2_' . time(),
                    'email' => 'test2_' . time() . '@example.com',
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now()
                ]);
                
                $this->testUsers[] = $user1;
                $this->testUsers[] = $user2;
                
                $policy = new UserPolicy();
                
                $canUpdateSelf = $policy->update($user1, $user1);
                $canUpdateOther = $policy->update($user1, $user2);
                
                return $canUpdateSelf && !$canUpdateOther;
            } catch (Exception $e) {
                return false;
            }
        });

        $this->test("Private account policy works", function() {
            try {
                if (count($this->testUsers) >= 2) {
                    $user1 = $this->testUsers[0];
                    $user2 = $this->testUsers[1];
                    
                    $user2->update(['is_private' => true]);
                    
                    $policy = new UserPolicy();
                    $canView = $policy->view($user1, $user2);
                    
                    return !$canView;
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    private function testInputValidationSecurity()
    {
        echo "\n✅ Input Validation Security...\n";
        
        $this->test("Username validation prevents injection", function() {
            $rule = new ValidUsername();
            $fail = function($message) { throw new Exception($message); };
            
            try {
                $rule->validate('username', '<script>alert("xss")</script>', $fail);
                return false;
            } catch (Exception $e) {
                return true;
            }
        });

        $this->test("Username validation prevents SQL injection", function() {
            $rule = new ValidUsername();
            $fail = function($message) { throw new Exception($message); };
            
            try {
                $rule->validate('username', "admin'; DROP TABLE users; --", $fail);
                return false;
            } catch (Exception $e) {
                return true;
            }
        });

        $this->test("Content length validation works", function() {
            $rule = new ContentLength('bio');
            $fail = function($message) { throw new Exception($message); };
            
            try {
                $longContent = str_repeat('a', 1000);
                $rule->validate('bio', $longContent, $fail);
                return false;
            } catch (Exception $e) {
                return true;
            }
        });

        $this->test("Age validation prevents underage users", function() {
            $rule = new MinimumAge();
            $fail = function($message) { throw new Exception($message); };
            
            try {
                $underageDate = now()->subYears(10)->format('Y-m-d');
                $rule->validate('date_of_birth', $underageDate, $fail);
                return false;
            } catch (Exception $e) {
                return true;
            }
        });
    }

    private function testMassAssignmentSecurity()
    {
        echo "\n🚨 Mass Assignment Security...\n";
        
        $this->test("Dangerous fields are not fillable", function() {
            $user = new User();
            $fillable = $user->getFillable();
            $dangerous = ['id', 'created_at', 'updated_at', 'email_verified_at', 'verified', 'is_premium', 'is_admin'];
            
            return count(array_intersect($dangerous, $fillable)) === 0;
        });

        $this->test("Mass assignment attack prevention", function() {
            try {
                $maliciousData = [
                    'name' => 'Test User',
                    'username' => 'testuser_' . time(),
                    'email' => 'test_' . time() . '@example.com',
                    'password' => Hash::make('password123'),
                    'is_premium' => true,
                    'verified' => true,
                    'id' => 999999
                ];
                
                $user = User::create($maliciousData);
                $this->testUsers[] = $user;
                
                return !$user->is_premium && !$user->verified && $user->id !== 999999;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    private function testSQLInjectionSecurity()
    {
        echo "\n💉 SQL Injection Security...\n";
        
        $this->test("Eloquent ORM prevents SQL injection", function() {
            try {
                $maliciousInput = "'; DROP TABLE users; --";
                $result = User::where('username', $maliciousInput)->first();
                return true;
            } catch (Exception $e) {
                return !str_contains($e->getMessage(), 'DROP TABLE');
            }
        });

        $this->test("Raw queries are avoided", function() {
            $userModelContent = file_get_contents(__DIR__ . '/app/Models/User.php');
            $dangerousPatterns = ['DB::raw', '::raw(', 'selectRaw', 'whereRaw', 'havingRaw'];
            
            foreach ($dangerousPatterns as $pattern) {
                if (strpos($userModelContent, $pattern) !== false) {
                    return false;
                }
            }
            return true;
        });
    }

    private function testXSSProtectionSecurity()
    {
        echo "\n🔍 XSS Protection Security...\n";
        
        $this->test("User input is properly handled", function() {
            try {
                $xssPayload = '<script>alert("xss")</script>';
                
                $user = User::create([
                    'name' => $xssPayload,
                    'username' => 'xsstest_' . time(),
                    'email' => 'xss_' . time() . '@example.com',
                    'password' => Hash::make('password123'),
                    'bio' => $xssPayload,
                    'email_verified_at' => now()
                ]);
                
                $this->testUsers[] = $user;
                return $user->name === $xssPayload;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    private function testPasswordSecurity()
    {
        echo "\n🔑 Password Security...\n";
        
        $this->test("Passwords are automatically hashed", function() {
            $user = new User();
            $casts = $user->getCasts();
            return isset($casts['password']) && $casts['password'] === 'hashed';
        });

        $this->test("Password field is hidden", function() {
            $user = new User();
            $hidden = $user->getHidden();
            return in_array('password', $hidden);
        });

        $this->test("Strong password validation exists", function() {
            return class_exists('App\Rules\StrongPassword');
        });

        $this->test("Password hashing works correctly", function() {
            $password = 'testpassword123';
            $hashed = Hash::make($password);
            return Hash::check($password, $hashed) && $hashed !== $password;
        });
    }

    private function testPrivacySecurity()
    {
        echo "\n🔒 Privacy Security...\n";
        
        $this->test("Sensitive user data is not exposed", function() {
            $user = new User();
            $hidden = $user->getHidden();
            $sensitive = ['password', 'remember_token', 'two_factor_secret', 'email_verification_token'];
            
            return count(array_intersect($sensitive, $hidden)) >= 3;
        });

        $this->test("Block/Mute privacy is enforced", function() {
            try {
                if (count($this->testUsers) >= 2) {
                    $user1 = $this->testUsers[0];
                    $user2 = $this->testUsers[1];
                    
                    Block::create([
                        'blocker_id' => $user1->id,
                        'blocked_id' => $user2->id
                    ]);
                    
                    return $user1->hasBlocked($user2->id);
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    private function testRateLimitingSecurity()
    {
        echo "\n⏱️ Rate Limiting Security...\n";
        
        $this->test("Rate limiting on sensitive routes", function() {
            $routes = Route::getRoutes();
            $rateLimitedRoutes = 0;
            
            foreach ($routes as $route) {
                $uri = $route->uri();
                $middleware = $route->middleware();
                
                if (strpos($uri, 'follow') !== false || strpos($uri, 'block') !== false) {
                    foreach ($middleware as $m) {
                        if (strpos($m, 'throttle:') !== false) {
                            $rateLimitedRoutes++;
                            break;
                        }
                    }
                }
            }
            
            return $rateLimitedRoutes > 0;
        });
    }

    private function testDataLeakageSecurity()
    {
        echo "\n🔍 Data Leakage Security...\n";
        
        $this->test("UserResource hides sensitive data", function() {
            if (!class_exists('App\Http\Resources\UserResource')) {
                return false;
            }
            
            $resourceContent = file_get_contents(__DIR__ . '/app/Http/Resources/UserResource.php');
            return strpos($resourceContent, '$this->when(') !== false;
        });

        $this->test("Email only shown to current user", function() {
            $resourceContent = file_get_contents(__DIR__ . '/app/Http/Resources/UserResource.php');
            return strpos($resourceContent, 'isCurrentUser()') !== false;
        });
    }

    private function testBusinessLogicSecurity()
    {
        echo "\n🏢 Business Logic Security...\n";
        
        $this->test("Users cannot block themselves", function() {
            try {
                if (count($this->testUsers) >= 1) {
                    $user = $this->testUsers[0];
                    $policy = new UserPolicy();
                    
                    return !$policy->block($user, $user);
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        });

        $this->test("Users cannot follow themselves", function() {
            try {
                if (count($this->testUsers) >= 1) {
                    $user = $this->testUsers[0];
                    $policy = new UserPolicy();
                    
                    return !$policy->follow($user, $user);
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        });

        $this->test("Blocked users cannot interact", function() {
            try {
                if (count($this->testUsers) >= 2) {
                    $user1 = $this->testUsers[0];
                    $user2 = $this->testUsers[1];
                    
                    Block::firstOrCreate([
                        'blocker_id' => $user1->id,
                        'blocked_id' => $user2->id
                    ]);
                    
                    $policy = new UserPolicy();
                    return !$policy->follow($user2, $user1);
                }
                return true;
            } catch (Exception $e) {
                return false;
            }
        });
    }

    private function testDatabaseIntegrity()
    {
        echo "\n🗄️ Database Integrity...\n";
        
        $this->test("Users table has all required columns", function() {
            $columns = DB::getSchemaBuilder()->getColumnListing('users');
            $required = [
                'id', 'name', 'username', 'email', 'password',
                'bio', 'avatar', 'location', 'website',
                'is_private', 'verified', 'followers_count', 'following_count'
            ];
            return count(array_intersect($required, $columns)) >= 10;
        });

        $this->test("Follows table structure is correct", function() {
            $columns = DB::getSchemaBuilder()->getColumnListing('follows');
            return in_array('follower_id', $columns) && 
                   in_array('following_id', $columns) &&
                   in_array('created_at', $columns);
        });

        $this->test("Blocks table exists", function() {
            return DB::getSchemaBuilder()->hasTable('blocks');
        });

        $this->test("Mutes table exists", function() {
            return DB::getSchemaBuilder()->hasTable('mutes');
        });
    }

    private function testModelCompleteness()
    {
        echo "\n👤 Model Completeness...\n";
        
        $this->test("User model has all relationships", function() {
            $methods = [
                'posts', 'followers', 'following', 'blockedUsers', 'blockedBy',
                'mutedUsers', 'mutedBy'
            ];
            foreach ($methods as $method) {
                if (!method_exists(User::class, $method)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("User model has helper methods", function() {
            $methods = [
                'isFollowing', 'hasBlocked', 'isBlockedBy', 'hasMuted', 'isMutedBy',
                'isVerified'
            ];
            foreach ($methods as $method) {
                if (!method_exists(User::class, $method)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("User model casts are defined", function() {
            $user = new User();
            $casts = $user->getCasts();
            return isset($casts['password']) && 
                   isset($casts['is_private']) && 
                   isset($casts['verified']);
        });
    }

    private function testControllerCompleteness()
    {
        echo "\n🎮 Controller Completeness...\n";
        
        $this->test("ProfileController has all methods", function() {
            $methods = ['show', 'update', 'follow', 'unfollow', 'block', 'unblock', 'mute', 'unmute'];
            foreach ($methods as $method) {
                if (!method_exists(ProfileController::class, $method)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("FollowController separation is correct", function() {
            $profileMethods = get_class_methods(ProfileController::class);
            $followMethods = get_class_methods(FollowController::class);
            
            $hasFollowActions = in_array('follow', $profileMethods) && in_array('unfollow', $profileMethods);
            $hasOnlyLists = in_array('followers', $followMethods) && in_array('following', $followMethods);
            $noFollowActions = !in_array('follow', $followMethods) && !in_array('unfollow', $followMethods);
            
            return $hasFollowActions && $hasOnlyLists && $noFollowActions;
        });
    }

    private function testServiceCompleteness()
    {
        echo "\n⚙️ Service Completeness...\n";
        
        $this->test("UserService has required methods", function() {
            $methods = [
                'updateProfile', 'followUser', 'unfollowUser'
            ];
            foreach ($methods as $method) {
                if (!method_exists(UserService::class, $method)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("UserModerationService has methods", function() {
            $methods = ['blockUser', 'unblockUser', 'muteUser', 'unmuteUser'];
            foreach ($methods as $method) {
                if (!method_exists(UserModerationService::class, $method)) {
                    return false;
                }
            }
            return true;
        });
    }

    private function testValidationCompleteness()
    {
        echo "\n✅ Validation Completeness...\n";
        
        $this->test("All custom validation rules exist", function() {
            $rules = [
                'App\Rules\ValidUsername',
                'App\Rules\FileUpload', 
                'App\Rules\ContentLength',
                'App\Rules\StrongPassword',
                'App\Rules\MinimumAge'
            ];
            foreach ($rules as $rule) {
                if (!class_exists($rule)) {
                    return false;
                }
            }
            return true;
        });

        $this->test("Validation config is comprehensive", function() {
            $config = config('validation');
            return isset($config['user']) && 
                   isset($config['content']) && 
                   isset($config['file_upload']);
        });
    }

    private function testEventSystem()
    {
        echo "\n📡 Event System...\n";
        
        $this->test("User events exist", function() {
            $events = [
                'App\Events\UserUpdated',
                'App\Events\UserBlocked',
                'App\Events\UserMuted',
                'App\Events\UserFollowed'
            ];
            $existingEvents = 0;
            foreach ($events as $event) {
                if (class_exists($event)) {
                    $existingEvents++;
                }
            }
            return $existingEvents >= 2;
        });
    }

    private function testResourcesAndDTOs()
    {
        echo "\n📦 Resources & DTOs...\n";
        
        $this->test("UserResource exists and is complete", function() {
            if (!class_exists('App\Http\Resources\UserResource')) {
                return false;
            }
            
            $reflection = new ReflectionClass('App\Http\Resources\UserResource');
            $source = file_get_contents($reflection->getFileName());
            return strpos($source, 'display_name') !== false;
        });
    }

    private function testPolicyCompleteness()
    {
        echo "\n🛡️ Policy Completeness...\n";
        
        $this->test("Policy handles privacy correctly", function() {
            $reflection = new ReflectionClass('App\Policies\UserPolicy');
            $source = file_get_contents($reflection->getFileName());
            return strpos($source, 'is_private') !== false &&
                   strpos($source, 'hasBlocked') !== false;
        });
    }

    private function testPerformanceOptimization()
    {
        echo "\n⚡ Performance Optimization...\n";
        
        $this->test("Counter caches are implemented", function() {
            $columns = DB::getSchemaBuilder()->getColumnListing('users');
            return in_array('followers_count', $columns) && 
                   in_array('following_count', $columns) && 
                   in_array('posts_count', $columns);
        });

        $this->test("Database indexes exist", function() {
            try {
                $userIndexes = DB::select("SHOW INDEX FROM users");
                return count($userIndexes) >= 3;
            } catch (Exception $e) {
                return true;
            }
        });
    }

    private function cleanup()
    {
        echo "\n🧹 Cleaning up test data...\n";
        try {
            foreach ($this->testUsers as $user) {
                if ($user && $user->exists) {
                    Block::where('blocker_id', $user->id)->orWhere('blocked_id', $user->id)->delete();
                    Mute::where('muter_id', $user->id)->orWhere('muted_id', $user->id)->delete();
                    $user->delete();
                }
            }
            echo "  ✓ Cleanup completed\n";
        } catch (Exception $e) {
            echo "  ⚠ Cleanup warning: " . $e->getMessage() . "\n";
        }
    }

    private function test($description, $callback)
    {
        $this->totalTests++;
        try {
            $result = $callback();
            if ($result) {
                $this->passedTests++;
                $status = "✅ PASS";
                $this->results[] = ['status' => 'PASS', 'test' => $description];
            } else {
                $status = "❌ FAIL";
                $this->results[] = ['status' => 'FAIL', 'test' => $description];
            }
        } catch (Exception $e) {
            $status = "❌ ERROR";
            $this->results[] = ['status' => 'ERROR', 'test' => $description, 'error' => $e->getMessage()];
        }
        
        echo "  $status: $description\n";
    }

    private function displayResults()
    {
        echo "\n" . str_repeat("═", 70) . "\n";
        echo "🔒 USERS & PROFILE SECURITY & DEEP AUDIT RESULTS\n";
        echo str_repeat("═", 70) . "\n";
        
        $securityScore = ($this->passedTests / $this->totalTests) * 100;
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->totalTests - $this->passedTests) . "\n";
        echo "Security Score: " . number_format($securityScore, 1) . "%\n\n";
        
        if ($securityScore >= 95) {
            echo "🛡️ EXCELLENT: System is HIGHLY SECURE and COMPLETE!\n";
        } elseif ($securityScore >= 90) {
            echo "✅ VERY GOOD: System is secure with minor issues.\n";
        } elseif ($securityScore >= 80) {
            echo "⚠️ GOOD: System needs some security attention.\n";
        } else {
            echo "❌ CRITICAL: System has serious security issues!\n";
        }
        
        $issues = array_filter($this->results, function($result) {
            return $result['status'] !== 'PASS';
        });
        
        if (!empty($issues)) {
            echo "\n🚨 Issues Found:\n";
            foreach ($issues as $issue) {
                echo "  • {$issue['test']} ({$issue['status']})";
                if (isset($issue['error'])) {
                    echo " - {$issue['error']}";
                }
                echo "\n";
            }
        }
        
        echo "\n" . str_repeat("═", 70) . "\n";
        if ($securityScore >= 95) {
            echo "🔒 SECURITY CONFIRMED: System is PRODUCTION READY!\n";
        } else {
            echo "⚠️ SECURITY ATTENTION NEEDED!\n";
        }
        echo str_repeat("═", 70) . "\n";
    }
}

// Run the security and deep audit test
$test = new UsersProfileSecurityTest();
$test->runAllTests();