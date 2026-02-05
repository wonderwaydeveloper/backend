<?php

namespace App\Contracts\Services;

use App\DTOs\LoginDTO;
use App\DTOs\UserRegistrationDTO;
use App\Models\User;

interface AuthServiceInterface
{
    public function register(UserRegistrationDTO $dto, ?\Illuminate\Http\Request $request = null): array;
    
    public function login(LoginDTO $loginDTO, bool $createToken = true, ?\Illuminate\Http\Request $request = null): array;
    
    public function logout(User $user): bool;
    
    public function refreshToken(string $refreshToken): array;
    
    public function forgotPassword(string $email, ?\Illuminate\Http\Request $request = null): bool;
    
    public function resetPassword(string $code, string $password, ?\Illuminate\Http\Request $request = null, ?string $email = null): bool;
    
    public function verifyEmail(string $token): bool;
    
    public function resendVerification(User $user): bool;
    
    public function enable2FA(User $user): array;
    
    public function verify2FA(User $user, string $code): bool;
    
    public function disable2FA(User $user): bool;
}