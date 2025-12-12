<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Services\AuthService;
use App\Services\PhoneVerificationService;
use App\Services\TwoFactorService;
use App\Models\UserSecurityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;
    private $phoneVerificationServiceMock;
    private $twoFactorServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->phoneVerificationServiceMock = Mockery::mock(PhoneVerificationService::class);
        $this->twoFactorServiceMock = Mockery::mock(TwoFactorService::class);

        $this->authService = new AuthService(
            $this->phoneVerificationServiceMock,
            $this->twoFactorServiceMock
        );
    }

    #[Test]
    public function it_registers_user_successfully()
    {
        $userData = [
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'birth_date' => '1990-01-01',
        ];

        $user = $this->authService->registerUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertFalse($user->is_underage);

        // Check security log was created
        $this->assertDatabaseHas('user_security_logs', [
            'user_id' => $user->id,
            'action' => 'registration',
        ]);
    }

    #[Test]
    public function it_marks_user_as_underage_if_under_18()
    {
        $userData = [
            'name' => 'Child User',
            'username' => 'childuser',
            'email' => 'child@example.com',
            'password' => 'password123',
            'birth_date' => now()->subYears(16)->format('Y-m-d'),
        ];

        $user = $this->authService->registerUser($userData);

        $this->assertTrue($user->fresh()->is_underage);
    }

    #[Test]
    public function it_logs_in_user_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $result = $this->authService->loginUser('test@example.com', 'password123');

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('two_factor_required', $result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals('test@example.com', $result['user']->email);

        // Check security log was created
        $this->assertDatabaseHas('user_security_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    #[Test]
    public function it_throws_exception_for_invalid_credentials()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->authService->loginUser('test@example.com', 'wrongpassword');
    }

    #[Test]
    public function it_throws_exception_for_banned_user()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Account is banned');

        $user = User::factory()->create([
            'email' => 'banned@example.com',
            'password' => Hash::make('password123'),
            'is_banned' => true,
        ]);

        $this->authService->loginUser('banned@example.com', 'password123');
    }

    #[Test]
    public function it_generates_unique_username()
    {
        // کاربرانی با نام‌های کاربری که تابع قرار است با آن‌ها تداخل پیدا کند، ایجاد می‌کنیم
        User::factory()->create(['username' => 'john-doe']);
        User::factory()->create(['username' => 'john-doe1']);

        $method = new \ReflectionMethod(AuthService::class, 'generateUniqueUsername');
        $method->setAccessible(true);

        $username1 = $method->invoke($this->authService, 'John Doe');
        $username2 = $method->invoke($this->authService, 'John Doe');

        // حالا هر دو فراخوانی باید 'john-doe2' را برگردانند، زیرا وضعیت دیتابیس بین دو فراخوانی تغییر نمی‌کند
        $this->assertEquals('john-doe2', $username1);
        $this->assertEquals($username2, $username1);

        // اگر می‌خواهید بررسی کنید که نام‌های متفاوتی تولید می‌شوند، باید منطق تست را تغییر دهید
        // برای مثال، کاربر دوم را بعد از اولین فراخوانی ایجاد کنید.
        $this->assertStringStartsWith('john-doe', $username1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}