<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(string $companyName, string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($companyName, $email, $secret);
    }

    public function verifyCode(string $secret, string $code): bool
    {
        try {
            return $this->google2fa->verifyKey($secret, $code);
        } catch (\Exception $e) {
            // Invalid secret or code format
            return false;
        }
    }

    public function generateBackupCodes(int $count = 8): array
    {
        $codes = [];
        $hashedCodes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = strtoupper(substr(bin2hex(random_bytes(8)), 0, 16)); // 16 characters
            $codes[] = $code;
            $hashedCodes[] = Hash::make($code);
        }
        
        // Return both plain codes (for user display) and hashed codes (for storage)
        return [
            'plain' => $codes,
            'hashed' => $hashedCodes
        ];
    }

    public function verifyPassword(\App\Models\User $user, string $password): bool
    {
        return Hash::check($password, $user->password);
    }

    public function decryptSecret(string $encryptedSecret): string
    {
        return decrypt($encryptedSecret);
    }
}
