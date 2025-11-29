<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Str;

class TwoFactorService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * فعال‌سازی احراز هویت دو مرحله‌ای
     */
    public function enableTwoFactor(User $user): array
    {
        return DB::transaction(function () use ($user) {
            $secret = $this->google2fa->generateSecretKey();
            $recoveryCodes = $this->generateRecoveryCodes();

            $user->update([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => json_encode($recoveryCodes),
            ]);

            $qrCodeUrl = $this->google2fa->getQRCodeUrl(
                config('app.name'),
                $user->email,
                $secret
            );

            return [
                'qr_code' => $qrCodeUrl,
                'recovery_codes' => $recoveryCodes,
                'secret' => $secret,
            ];
        });
    }

    /**
     * غیرفعال‌سازی احراز هویت دو مرحله‌ای
     */
    public function disableTwoFactor(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->update([
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ]);
        });
    }

    /**
     * تأیید کد احراز هویت دو مرحله‌ای
     */
    public function verifyTwoFactorCode(User $user, string $code): string
    {
        if ($this->google2fa->verifyKey($user->two_factor_secret, $code)) {
            $user->update(['two_factor_enabled' => true]);
            return $user->createToken('auth-token')->plainTextToken;
        }

        // بررسی کدهای بازیابی
        if ($this->verifyRecoveryCode($user, $code)) {
            $user->update(['two_factor_enabled' => true]);
            return $user->createToken('auth-token')->plainTextToken;
        }

        throw new \Exception('Invalid two-factor code');
    }

    /**
     * تولید کدهای بازیابی
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }
        return $codes;
    }

    /**
     * تأیید کد بازیابی
     */
    private function verifyRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = json_decode($user->two_factor_recovery_codes, true) ?? [];

        if (in_array($code, $recoveryCodes)) {
            // حذف کد استفاده شده
            $recoveryCodes = array_diff($recoveryCodes, [$code]);
            $user->update(['two_factor_recovery_codes' => json_encode(array_values($recoveryCodes))]);
            return true;
        }

        return false;
    }

    /**
     * تولید کدهای بازیابی جدید
     */
    public function generateNewRecoveryCodes(User $user): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        return $recoveryCodes;
    }
}