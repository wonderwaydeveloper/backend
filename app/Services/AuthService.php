<?php

namespace App\Services;

use App\Models\PhoneVerification;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserSecurityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private PhoneVerificationService $phoneVerificationService,
        private TwoFactorService $twoFactorService
    ) {}

    /**
     * ثبت‌نام کاربر با ایمیل
     */
    public function registerUser(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'birth_date' => $data['birth_date'],
                'is_underage' => $this->calculateUnderageStatus($data['birth_date']),
            ]);

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent($user, 'registration');

            return $user;
        });
    }

    /**
     * ورود کاربر با ایمیل
     */
    public function loginUser(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        if ($user->is_banned) {
            throw new \Exception('Account is banned');
        }

        // بررسی احراز هویت دو مرحله‌ای
        $twoFactorRequired = $user->two_factor_enabled;

        if (!$twoFactorRequired) {
            $token = $user->createToken('auth-token')->plainTextToken;
            $this->updateLastLogin($user);
        }

        UserSecurityLog::logSecurityEvent($user, 'login');

        return [
            'user' => $user,
            'token' => $token ?? null,
            'two_factor_required' => $twoFactorRequired,
        ];
    }

    /**
     * ثبت‌نام با شماره موبایل
     */
    public function registerWithPhone(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // بررسی کد تأیید
            $verification = $this->phoneVerificationService->verifyCode(
                $data['phone'],
                $data['code'],
                $data['token']
            );

            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'phone' => $data['phone'],
                'phone_verified_at' => now(),
                'password' => Hash::make(Str::random(16)), // رمز تصادفی
                'birth_date' => $data['birth_date'],
                'is_underage' => $this->calculateUnderageStatus($data['birth_date']),
            ]);

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent($user, 'registration_phone');

            return $user;
        });
    }

    /**
     * ورود با شماره موبایل
     */
    public function loginWithPhone(string $phone, string $code): array
    {
        // بررسی کد تأیید
        $verification = $this->phoneVerificationService->verifyCode($phone, $code);

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            throw new \Exception('User not found');
        }

        if ($user->is_banned) {
            throw new \Exception('Account is banned');
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $this->updateLastLogin($user);

        UserSecurityLog::logSecurityEvent($user, 'login_phone');

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * مدیریت احراز هویت اجتماعی
     */
    public function handleSocialLogin(string $provider, $socialUser): array
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            // بررسی وجود حساب اجتماعی
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialAccount) {
                $user = $socialAccount->user;
            } else {
                // ایجاد کاربر جدید
                $user = User::where('email', $socialUser->getEmail())->first();

                if (!$user) {
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'username' => $this->generateUniqueUsername($socialUser->getNickname() ?? $socialUser->getName()),
                        'email' => $socialUser->getEmail(),
                        'password' => Hash::make(Str::random(16)),
                        'email_verified_at' => now(),
                        'avatar' => $socialUser->getAvatar(),
                    ]);
                }

                // ایجاد حساب اجتماعی
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                    'token' => $socialUser->token,
                    'refresh_token' => $socialUser->refreshToken,
                    'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
                    'profile' => $socialUser->user,
                ]);
            }

            if ($user->is_banned) {
                throw new \Exception('Account is banned');
            }

            $token = $user->createToken('auth-token')->plainTextToken;
            $this->updateLastLogin($user);

            UserSecurityLog::logSecurityEvent($user, 'login_social', ['provider' => $provider]);

            return [
                'user' => $user,
                'token' => $token,
            ];
        });
    }

    /**
     * بررسی معتبر بودن provider
     */
    public function validateProvider(string $provider): bool
    {
        return in_array($provider, ['google', 'facebook', 'github']);
    }

    /**
     * محاسبه وضعیت سنی
     */
    private function calculateUnderageStatus(string $birthDate): bool
    {
        $age = now()->diffInYears($birthDate);
        return $age < 18;
    }

    /**
     * تولید نام کاربری یکتا
     */
    private function generateUniqueUsername(string $base): string
    {
        $username = Str::slug($base);
        $originalUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * آپدیت زمان آخرین ورود
     */
    private function updateLastLogin(User $user): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }
}