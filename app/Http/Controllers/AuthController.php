<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Http\Resources\GenericResource;
use App\Models\PhoneVerification;
use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserSecurityLog;
use App\Services\AuthService;
use App\Services\PhoneVerificationService;
use App\Services\TwoFactorService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Services\EmailVerificationService;
use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private PhoneVerificationService $phoneVerificationService,
        private EmailVerificationService $emailVerificationService
    ) {
    }


    /**
     * ثبت‌نام با ایمیل - بدون توکن
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'birth_date' => 'required|date|before:-10 years',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            // ایجاد کاربر با وضعیت pending
            $user = $this->authService->registerUser($request->all());

            // ارسال کد تأیید ایمیل (مطابق توییتر - اجباری)
            $verification = $this->emailVerificationService->sendVerificationEmail($user);

            // **تغییر مهم: هیچ توکنی ایجاد نمی‌شود**

            return GenericResource::success([
                'user_id' => $user->id,
                'email' => $user->email,
                'requires_verification' => true,
                'message' => 'Registration initiated. Please check your email for verification code to complete registration.',
                'next_step' => [
                    'action' => 'verify_email',
                    'endpoint' => '/api/auth/verify-and-login',
                    'description' => 'Verify your email to activate your account'
                ]
            ], 'Verification required', 202);

        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * تأیید ایمیل و لاگین (مرحله دوم ثبت‌نام)
     */
    public function verifyEmailAndLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            // تأیید کد
            $verified = $this->emailVerificationService->verifyEmail(
                $request->email,
                $request->code
            );

            if (!$verified) {
                return GenericResource::error('Invalid or expired verification code', 400);
            }

            // پیدا کردن کاربر
            $user = User::where('email', $request->email)->firstOrFail();

            // فعال‌سازی حساب (مطابق توییتر)
            $user->update([
                'email_verified_at' => now(),
                'status' => 'active'
            ]);

            // **اینجا اولین توکن ایجاد می‌شود**
            $token = $user->createToken('auth-token')->plainTextToken;

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent($user, 'registration_completed');

            return new AuthResource([
                'user' => $user,
                'token' => $token,
                'message' => 'Account activated successfully! Welcome.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return GenericResource::error('User not found', 404);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

  /**
     * ورود با ایمیل - فقط برای کاربران تأیید شده
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $result = $this->authService->loginUser($request->email, $request->password);

            return new AuthResource([
                'user' => $result['user'],
                'token' => $result['token'],
                'message' => 'Login successful'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 401);
        }
    }

    /**
     * ارسال کد تأیید برای شماره موبایل
     */
    public function sendPhoneVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|unique:users,phone',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $verification = $this->phoneVerificationService->sendVerificationCode($request->phone);

            return GenericResource::success([
                'token' => $verification->token,
                'expires_at' => $verification->expires_at,
            ], 'Verification code sent successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * ثبت‌نام با شماره موبایل
     */
    public function registerWithPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|max:255|unique:users',
            'phone' => 'required|string|unique:users',
            'code' => 'required|string|size:6',
            'token' => 'required|string',
            'birth_date' => 'required|date|before:-10 years',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $user = $this->authService->registerWithPhone($request->all());
            $token = $user->createToken('auth-token')->plainTextToken;

            return new AuthResource([
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully with phone'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * ورود با شماره موبایل
     */
    public function loginWithPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $result = $this->authService->loginWithPhone($request->phone, $request->code);

            return new AuthResource([
                'user' => $result['user'],
                'token' => $result['token'],
                'message' => 'Login successful with phone'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 401);
        }
    }

    /**
     * هدایت به صفحه احراز هویت اجتماعی
     */
    public function redirectToProvider($provider)
    {
        $validated = $this->authService->validateProvider($provider);
        if (!$validated) {
            return GenericResource::error('Invalid provider', 400);
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    /**
     * بازگشت از احراز هویت اجتماعی
     */
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            $result = $this->authService->handleSocialLogin($provider, $socialUser);

            return new AuthResource([
                'user' => $result['user'],
                'token' => $result['token'],
                'message' => 'Social login successful'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }


    /**
     * خروج کاربر (فقط توکن جاری)
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $currentToken = $request->bearerToken();

            \Log::info('Logout attempt (current token only)', [
                'user_id' => $user->id,
                'has_token' => !empty($currentToken),
            ]);

            if ($currentToken) {
                // پیدا کردن توکن فعلی
                $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($currentToken);

                if ($tokenModel) {
                    // فقط همین توکن را حذف کنیم
                    $tokenModel->delete();

                    // پاک کردن کش این توکن خاص
                    \Illuminate\Support\Facades\Cache::forget('sanctum-token-' . $currentToken);

                    \Log::info('Current token deleted', [
                        'user_id' => $user->id,
                        'token_id' => $tokenModel->id,
                        'name' => $tokenModel->name,
                        'deleted_count' => 1
                    ]);
                } else {
                    \Log::warning('Current token not found for deletion', [
                        'user_id' => $user->id,
                        'token' => substr($currentToken, 0, 20) . '...'
                    ]);
                }
            }

            return GenericResource::success(null, 'Logged out successfully (current session only)');
        } catch (\Exception $e) {
            \Log::error('Logout failed', ['error' => $e->getMessage()]);
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * دریافت اطلاعات کاربر جاری
     */
    public function user(Request $request)
    {
        return new AuthResource([
            'user' => $request->user()->load('followers', 'following'),
            'message' => 'User data retrieved successfully'
        ]);
    }

    /**
     * ارسال کد تأیید ایمیل
     */
    public function sendEmailVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $user = User::where('email', $request->email)->first();

            if ($user->email_verified_at) {
                return GenericResource::error('Email already verified', 400);
            }

            $verification = $this->emailVerificationService->sendVerificationEmail($user);

            return GenericResource::success([
                'token' => $verification->token,
                'expires_at' => $verification->expires_at,
            ], 'Verification email sent successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * تأیید ایمیل
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $verified = $this->emailVerificationService->verifyEmail(
                $request->email,
                $request->code
            );

            return GenericResource::success([
                'verified' => $verified,
            ], 'Email verified successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * درخواست بازیابی رمز عبور
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $verification = $this->emailVerificationService->sendPasswordResetEmail($request->email);

            return GenericResource::success([
                'token' => $verification->token,
                'expires_at' => $verification->expires_at,
            ], 'Password reset email sent successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * تأیید کد بازیابی رمز عبور
     */
    public function verifyPasswordReset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $result = $this->emailVerificationService->verifyPasswordReset(
                $request->email,
                $request->code
            );

            return GenericResource::success([
                'reset_token' => $result['token'],
            ], 'Password reset code verified successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * بازنشانی رمز عبور
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $reset = $this->emailVerificationService->resetPassword(
                $request->token,
                $request->password
            );

            return GenericResource::success([
                'reset' => $reset,
            ], 'Password reset successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }


    /**
     * مشاهده session‌های فعال کاربر
     */

    public function activeSessions(Request $request)
    {
        try {
            \Log::info('ActiveSessions endpoint called', [
                'user_id' => $request->user()->id,
                'has_user' => !is_null($request->user()),
            ]);

            $user = $request->user();

            if (!$user) {
                return GenericResource::error('User not authenticated', 401);
            }

            // دیباگ: بررسی tokens
            \Log::info('User tokens count: ' . $user->tokens()->count());

            $tokens = $user->tokens()
                ->select(['id', 'name', 'last_used_at', 'created_at', 'ip_address', 'user_agent'])
                ->orderBy('last_used_at', 'desc')
                ->get();

            \Log::info('Tokens retrieved: ' . $tokens->count());

            $mappedTokens = $tokens->map(function ($token) use ($user) {
                $currentToken = $user->currentAccessToken();

                return [
                    'id' => $token->id,
                    'name' => $token->name ?? 'Unknown',
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at->toISOString(),
                    'ip_address' => $token->ip_address ?? 'Unknown',
                    'user_agent' => $token->user_agent ?? 'Unknown',
                    'is_current' => $currentToken && $token->id === $currentToken->id,
                    'device_type' => $this->detectDeviceType($token->user_agent),
                ];
            });

            return GenericResource::success($mappedTokens, 'Active sessions retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('ActiveSessions error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return GenericResource::error('Failed to retrieve sessions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * حذف session خاص
     */
    public function revokeSession(Request $request, $tokenId)
    {
        try {
            $token = $request->user()->tokens()->find($tokenId);

            if (!$token) {
                return GenericResource::error('Session not found', 404);
            }

            // نمی‌توان session جاری را حذف کرد (باید از logout استفاده شود)
            $currentToken = $request->user()->currentAccessToken();
            if ($currentToken && $token->id === $currentToken->id) {
                return GenericResource::error('Cannot revoke current session. Use logout instead.', 400);
            }

            $token->delete();

            return GenericResource::success(null, 'Session revoked successfully');
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * حذف همه session‌ها به جز جاری
     */
    public function revokeOtherSessions(Request $request)
    {
        try {
            $currentToken = $request->user()->currentAccessToken();

            if (!$currentToken) {
                return GenericResource::error('No active session found', 400);
            }

            $deletedCount = $request->user()->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();

            // لاگ امنیتی
            UserSecurityLog::logSecurityEvent(
                $request->user(),
                'revoke_other_sessions',
                ['revoked_count' => $deletedCount]
            );

            return GenericResource::success(
                ['revoked_count' => $deletedCount],
                'All other sessions revoked successfully'
            );
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * لاگ‌اوت از همه دستگاه‌ها (همه توکن‌ها، شامل توکن جاری)
     */
    public function logoutFromAllDevices(Request $request)
    {
        try {
            $user = $request->user();

            // شمارش توکن‌ها قبل از حذف
            $tokensBefore = DB::table('personal_access_tokens')
                ->where('tokenable_type', get_class($user))
                ->where('tokenable_id', $user->id)
                ->count();

            \Log::info('Logout from ALL devices (including current)', [
                'user_id' => $user->id,
                'tokens_before' => $tokensBefore,
            ]);

            // حذف ALL توکن‌ها (شامل توکن جاری)
            $deletedCount = DB::table('personal_access_tokens')
                ->where('tokenable_type', get_class($user))
                ->where('tokenable_id', $user->id)
                ->delete();

            // **پاک کردن کش Sanctum**
            $this->clearSanctumCacheForUser($user);

            \Log::info('All tokens deleted (including current)', [
                'user_id' => $user->id,
                'deleted_count' => $deletedCount,
            ]);

            return GenericResource::success(null, 'Logged out from ALL devices successfully (including this device)');
        } catch (\Exception $e) {
            \Log::error('Logout from all devices failed', ['error' => $e->getMessage()]);
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * پاک کردن کش Sanctum برای کاربر خاص
     */
    private function clearSanctumCacheForUser(User $user)
    {
        try {
            // پیدا کردن همه توکن‌های کاربر قبل از حذف (برای پاک کردن کش)
            $tokens = DB::table('personal_access_tokens')
                ->where('tokenable_type', get_class($user))
                ->where('tokenable_id', $user->id)
                ->get(['token']);

            foreach ($tokens as $tokenRecord) {
                // پاک کردن کش این توکن خاص
                $cacheKey = 'sanctum-token-' . $tokenRecord->token;
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            // همچنین پاک کردن cacheهای مرتبط با کاربر
            \Illuminate\Support\Facades\Cache::tags(['user-' . $user->id, 'sanctum'])->flush();

            \Log::info('Sanctum cache cleared for user', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            \Log::warning('Failed to clear Sanctum cache', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * تشخیص نوع دستگاه از User-Agent
     */
    private function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (
            strpos($userAgent, 'mobile') !== false ||
            strpos($userAgent, 'android') !== false ||
            strpos($userAgent, 'iphone') !== false
        ) {
            return 'mobile';
        }

        if (
            strpos($userAgent, 'tablet') !== false ||
            strpos($userAgent, 'ipad') !== false
        ) {
            return 'tablet';
        }

        if (
            strpos($userAgent, 'windows') !== false ||
            strpos($userAgent, 'macintosh') !== false ||
            strpos($userAgent, 'linux') !== false
        ) {
            return 'desktop';
        }

        return 'other';
    }

}