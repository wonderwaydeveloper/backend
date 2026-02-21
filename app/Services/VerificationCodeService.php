<?php

namespace App\Services;

use Illuminate\Support\Str;

class VerificationCodeService
{
    public function generateCode(): int
    {
        return random_int(
            config('security.email.code_min', 100000),
            config('security.email.code_max', 999999)
        );
    }

    public function generateSessionId(): string
    {
        return Str::uuid()->toString();
    }

    public function getExpiryMinutes(): int
    {
        return config('security.email.verification_expire_minutes', 15);
    }

    public function getCodeExpiryTimestamp(): int
    {
        return now()->addMinutes($this->getExpiryMinutes())->timestamp;
    }

    public function getResendAvailableTimestamp(int $seconds = 60): int
    {
        return now()->addSeconds($seconds)->timestamp;
    }
}
