<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Http\Resources\GenericResource;
use App\Http\Resources\TwoFactorResource;
use App\Models\PhoneVerification;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\AuthService;
use App\Services\PhoneVerificationService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use App\Services\EmailVerificationService;

use Illuminate\Validation\ValidationException; 

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private PhoneVerificationService $phoneVerificationService,
        private TwoFactorService $twoFactorService,
        private EmailVerificationService $emailVerificationService
    ) {
    }

    /**
     * ثبت‌نام با ایمیل
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

        // **اصلاح نهایی:** پرتاب کردن استثنا به جای برگرداندن پاسخ دستی
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $user = $this->authService->registerUser($request->all());
            $token = $user->createToken('auth-token')->plainTextToken;

            return new AuthResource([
                'user' => $user,
                'token' => $token,
                'message' => 'User registered successfully'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 500);
        }
    }

    /**
     * ورود با ایمیل
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // **اصلاح نهایی:** پرتاب کردن استثنا به جای برگرداندن پاسخ دستی
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        try {
            $result = $this->authService->loginUser($request->email, $request->password);

            if ($result['two_factor_required']) {
                return new AuthResource([
                    'user' => $result['user'],
                    'two_factor_required' => true,
                    'message' => 'Two-factor authentication required'
                ]);
            }

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
     * فعال‌سازی احراز هویت دو مرحله‌ای
     */
    public function enableTwoFactor(Request $request)
    {
        try {
            $result = $this->twoFactorService->enableTwoFactor($request->user());

            return new TwoFactorResource([
                'enabled' => true,
                'qr_code' => $result['qr_code'],
                'recovery_codes' => $result['recovery_codes'],
                'message' => 'Two-factor authentication enabled successfully'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * غیرفعال‌سازی احراز هویت دو مرحله‌ای
     */
    public function disableTwoFactor(Request $request)
    {
        try {
            $this->twoFactorService->disableTwoFactor($request->user());

            return new TwoFactorResource([
                'enabled' => false,
                'message' => 'Two-factor authentication disabled successfully'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 400);
        }
    }

    /**
     * تأیید کد احراز هویت دو مرحله‌ای
     */
    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return GenericResource::error('Validation failed', 422, $validator->errors());
        }

        try {
            $token = $this->twoFactorService->verifyTwoFactorCode(
                $request->user(),
                $request->code
            );

            return new AuthResource([
                'user' => $request->user(),
                'token' => $token,
                'message' => 'Two-factor authentication verified successfully'
            ]);
        } catch (\Exception $e) {
            return GenericResource::error($e->getMessage(), 401);
        }
    }

    /**
     * خروج کاربر
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return GenericResource::success(null, 'Logged out successfully');
        } catch (\Exception $e) {
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
}