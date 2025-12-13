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
    ) {
    }

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
                'status' => 'pending', // وضعیت اولیه
                'email_verified_at' => null, // هنوز تأیید نشده
            ]);

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent($user, 'registration_pending');

            return $user;
        });
    }

    /**
     * ورود کاربر با ایمیل - فقط کاربران active می‌توانند لاگین کنند
     */
    public function loginUser(string $email, string $password): array
    {
        $user = User::where('email', $email)
            ->whereNotNull('email_verified_at') // فقط ایمیل‌های تأیید شده
            ->where('status', 'active') // فقط کاربران active
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials or account not verified');
        }

        if ($user->is_banned) {
            throw new \Exception('Account is banned');
        }

        // پاک کردن هرگونه کش قدیمی
        $this->clearUserOldTokensCache($user);

        // بررسی احراز هویت دو مرحله‌ای
        $twoFactorRequired = $user->two_factor_enabled;

        if (!$twoFactorRequired) {
            $token = $user->createToken('auth-token');

            // ذخیره اطلاعات session
            $token->accessToken->update([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $tokenPlainText = $token->plainTextToken;
            $this->updateLastLogin($user);
        }

        UserSecurityLog::logSecurityEvent($user, 'login');

        return [
            'user' => $user,
            'token' => $tokenPlainText ?? null,
            'two_factor_required' => $twoFactorRequired,
        ];
    }


    /**
     * پاک کردن کش توکن‌های قدیمی کاربر
     */
    private function clearUserOldTokensCache(User $user)
    {
        try {
            // پاک کردن cacheهای مرتبط با کاربر
            \Illuminate\Support\Facades\Cache::tags(['user-' . $user->id])->flush();

            \Log::info('Old user tokens cache cleared', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            \Log::warning('Failed to clear old user tokens cache', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * ثبت‌نام با شماره موبایل - وضعیت active
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
                'status' => 'active', // فعال زیرا تلفن تأیید شده
            ]);

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent($user, 'registration_phone');

            return $user;
        });
    }


    /**
     * ورود با شماره موبایل - فقط کاربران active
     */
    public function loginWithPhone(string $phone, string $code): array
    {
        // بررسی کد تأیید
        $verification = $this->phoneVerificationService->verifyCode($phone, $code);

        $user = User::where('phone', $phone)
            ->whereNotNull('phone_verified_at') // تلفن تأیید شده
            ->where('status', 'active') // فقط کاربران active
            ->first();

        if (!$user) {
            throw new \Exception('User not found or account not verified');
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
            'last_login_user_agent' => request()->userAgent(),
        ]);
    }

}