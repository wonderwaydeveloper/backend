<?php

namespace App\Services;

use App\Jobs\SendVerificationEmail;
use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationService
{

    /**
     * ارسال کد تأیید ایمیل
     */
    public function sendVerificationEmail(User $user): EmailVerification
    {
        return DB::transaction(function () use ($user) {
            $verification = EmailVerification::createVerification(
                $user->email,
                'verification'
            );

            // محاسبه زمان انقضا به دقیقه
            $expiresIn = now()->diffInMinutes($verification->expires_at);

            // ارسال ایمیل از طریق queue
            SendVerificationEmail::dispatch(
                $user->email,
                $verification->code,
                'verification',
                $user->name,
                $expiresIn // ارسال زمان انقضا
            );

            return $verification;
        });
    }

    /**
     * ارسال کد بازیابی رمز عبور
     */
    public function sendPasswordResetEmail(string $email): EmailVerification
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw new \Exception('User not found with this email');
        }

        return DB::transaction(function () use ($user) {
            $verification = EmailVerification::createVerification(
                $user->email,
                'password_reset'
            );

            // محاسبه زمان انقضا به دقیقه
            $expiresIn = now()->diffInMinutes($verification->expires_at);

            // ارسال ایمیل از طریق queue
            SendVerificationEmail::dispatch(
                $user->email,
                $verification->code,
                'password_reset',
                $user->name,
                $expiresIn // ارسال زمان انقضا
            );

            return $verification;
        });
    }

    /**
     * تأیید کد ایمیل
     */
    public function verifyEmail(string $email, string $code): bool
    {
        $verification = EmailVerification::where('email', $email)
            ->where('code', $code)
            ->where('type', 'verification')
            ->valid()
            ->first();

        if (!$verification) {
            throw new \Exception('Invalid or expired verification code');
        }

        return DB::transaction(function () use ($verification) {
            $verification->verify();

            // فعال‌سازی ایمیل کاربر
            $user = User::where('email', $verification->email)->first();
            if ($user) {
                $user->update(['email_verified_at' => now()]);
            }

            return true;
        });
    }

    /**
     * تأیید کد بازیابی رمز عبور
     */
    public function verifyPasswordReset(string $email, string $code): array
    {
        $verification = EmailVerification::where('email', $email)
            ->where('code', $code)
            ->where('type', 'password_reset')
            ->valid()
            ->first();

        if (!$verification) {
            throw new \Exception('Invalid or expired verification code');
        }

        $verification->verify();

        return [
            'token' => $verification->token,
            'email' => $verification->email,
        ];
    }

    /**
     * بازیابی رمز عبور
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $verification = EmailVerification::where('token', $token)
            ->where('type', 'password_reset')
            ->whereNotNull('verified_at')
            ->first();

        if (!$verification) {
            throw new \Exception('Invalid or expired reset token');
        }

        return DB::transaction(function () use ($verification, $newPassword) {
            $user = User::where('email', $verification->email)->first();

            if (!$user) {
                throw new \Exception('User not found');
            }

            $user->update([
                'password' => Hash::make($newPassword),
            ]);

            // حذف تمام verification های استفاده شده
            EmailVerification::where('email', $verification->email)
                ->where('type', 'password_reset')
                ->delete();

            return true;
        });
    }

    /**
     * بررسی معتبر بودن ایمیل
     */
    public function isValidVerification(string $email, string $code, string $type): bool
    {
        return EmailVerification::where('email', $email)
            ->where('code', $code)
            ->where('type', $type)
            ->valid()
            ->exists();
    }
}