<?php

namespace App\Services;

use App\Models\PhoneVerification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PhoneVerificationService
{
    /**
     * ارسال کد تأیید
     */
    public function sendVerificationCode(string $phone): PhoneVerification
    {
        return DB::transaction(function () use ($phone) {
            // غیرفعال کردن کدهای قبلی
            PhoneVerification::where('phone', $phone)
                ->whereNull('verified_at')
                ->update(['verified_at' => now()]);

            // ایجاد کد جدید
            $verification = PhoneVerification::create([
                'phone' => $phone,
                'code' => $this->generateVerificationCode(),
                'token' => Str::random(32),
                'expires_at' => now()->addMinutes(10),
            ]);

            // اینجا باید سرویس SMS فراخوانی شود
            $this->sendSMS($phone, $verification->code);

            return $verification;
        });
    }

    /**
     * تأیید کد
     */
    public function verifyCode(string $phone, string $code, string $token = null): PhoneVerification
    {
        $query = PhoneVerification::where('phone', $phone)
            ->where('code', $code)
            ->valid();

        if ($token) {
            $query->where('token', $token);
        }

        $verification = $query->first();

        if (!$verification) {
            throw new \Exception('Invalid or expired verification code');
        }

        if ($verification->isExpired()) {
            throw new \Exception('Verification code expired');
        }

        $verification->verify();

        return $verification;
    }

    /**
     * تولید کد تأیید
     */
    private function generateVerificationCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * ارسال SMS (پیاده‌سازی نمونه)
     */
    private function sendSMS(string $phone, string $code): void
    {
        // اینجا باید از سرویس SMS واقعی استفاده کنید
        // برای نمونه فقط لاگ می‌کنیم
        \Log::info("SMS sent to {$phone}: Verification code is {$code}");

        // نمونه استفاده از Kavenegar:
        // try {
        //     $api = new \Kavenegar\KavenegarApi(env('KAVENEGAR_API_KEY'));
        //     $api->Send(env('KAVENEGAR_SENDER'), $phone, "کد تأیید شما: {$code}");
        // } catch (\Exception $e) {
        //     throw new \Exception('Failed to send SMS');
        // }
    }
}