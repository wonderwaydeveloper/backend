<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class PasswordSecurityService
{
    private const HISTORY_LIMIT = 5;
    private const MIN_AGE_HOURS = 1;
    private const MAX_AGE_DAYS = 90;
    
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        $config = config('security.password');
        
        // Length check
        if (strlen($password) < $config['min_length']) {
            $errors[] = "Password must be at least {$config['min_length']} characters";
        }
        
        // Character requirements
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letters';
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letters';
        }
        
        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain numbers';
        }
        
        if ($config['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain special characters';
        }
        
        // Common password check
        if ($config['check_common_passwords'] && $this->isCommonPassword($password)) {
            $errors[] = 'Password is too common';
        }
        
        return $errors;
    }
    
    public function canChangePassword(int $userId): bool
    {
        $lastChange = Redis::get("password_last_change:{$userId}");
        
        if (!$lastChange) {
            return true;
        }
        
        $lastChangeTime = Carbon::createFromTimestamp($lastChange);
        $minAge = Carbon::now()->subHours(self::MIN_AGE_HOURS);
        
        return $lastChangeTime->lt($minAge);
    }
    
    public function isPasswordExpired(User $user): bool
    {
        if (!$user->password_changed_at) {
            return true; // Force change if never set
        }
        
        $maxAge = Carbon::now()->subDays(self::MAX_AGE_DAYS);
        return $user->password_changed_at->lt($maxAge);
    }
    
    public function checkPasswordHistory(int $userId, string $newPassword): bool
    {
        $history = $this->getPasswordHistory($userId);
        
        foreach ($history as $oldHash) {
            if (Hash::check($newPassword, $oldHash)) {
                return false; // Password was used before
            }
        }
        
        return true;
    }
    
    public function updatePassword(User $user, string $newPassword): void
    {
        // Validate strength
        $errors = $this->validatePasswordStrength($newPassword);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
        
        // Check minimum age
        if (!$this->canChangePassword($user->id)) {
            throw new \InvalidArgumentException('Password was changed too recently');
        }
        
        // Check history
        if (!$this->checkPasswordHistory($user->id, $newPassword)) {
            throw new \InvalidArgumentException('Password was used recently');
        }
        
        // Store old password in history
        if ($user->password) {
            $this->addToPasswordHistory($user->id, $user->password);
        }
        
        // Update password
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now()
        ]);
        
        // Update last change timestamp
        Redis::set("password_last_change:{$user->id}", time());
        
        // Invalidate all sessions except current
        $this->invalidateOtherSessions($user->id);
    }
    
    private function getPasswordHistory(int $userId): array
    {
        $history = Redis::lrange("password_history:{$userId}", 0, self::HISTORY_LIMIT - 1);
        return $history ?: [];
    }
    
    private function addToPasswordHistory(int $userId, string $passwordHash): void
    {
        $key = "password_history:{$userId}";
        
        // Add to front of list
        Redis::lpush($key, $passwordHash);
        
        // Keep only last N passwords
        Redis::ltrim($key, 0, self::HISTORY_LIMIT - 1);
        
        // Set expiration
        Redis::expire($key, 86400 * 365); // 1 year
    }
    
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            '1234567890', 'password1', '123123', 'qwerty123',
            'dragon', 'master', 'hello', 'login', 'princess',
            'solo', 'qwertyuiop', 'starwars', 'superman'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    private function invalidateOtherSessions(int $userId): void
    {
        // Get current session
        $currentSession = session()->getId();
        
        // Get all user sessions
        $sessions = Redis::smembers("user_sessions:{$userId}");
        
        foreach ($sessions as $sessionId) {
            if ($sessionId !== $currentSession) {
                // Invalidate session
                Redis::del("session:{$userId}:{$sessionId}");
                Redis::srem("user_sessions:{$userId}", $sessionId);
            }
        }
    }
    
    public function getPasswordStrengthScore(string $password): int
    {
        $score = 0;
        
        // Length bonus
        $score += min(25, strlen($password) * 2);
        
        // Character variety
        if (preg_match('/[a-z]/', $password)) $score += 5;
        if (preg_match('/[A-Z]/', $password)) $score += 5;
        if (preg_match('/[0-9]/', $password)) $score += 5;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $score += 10;
        
        // Patterns penalty
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10; // Repeated chars
        if (preg_match('/123|abc|qwe/i', $password)) $score -= 10; // Sequential
        
        // Common password penalty
        if ($this->isCommonPassword($password)) $score -= 25;
        
        return max(0, min(100, $score));
    }
}