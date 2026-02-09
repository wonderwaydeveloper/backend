<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidUsername implements ValidationRule
{
    private ?int $ignoreUserId;
    
    private array $reservedUsernames = [
        'admin', 'api', 'www', 'mail', 'ftp', 'localhost', 'root',
        'support', 'help', 'about', 'wonderway', 'wonder', 'way',
        'follow', 'following', 'followers', 'home', 'search',
        'settings', 'privacy', 'terms', 'notifications', 'messages',
        'explore', 'trending', 'moments', 'lists', 'bookmarks'
    ];

    public function __construct(?int $ignoreUserId = null)
    {
        $this->ignoreUserId = $ignoreUserId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Length check (4-15 characters)
        if (strlen($value) < 4 || strlen($value) > 15) {
            $fail('Username must be between 4 and 15 characters.');
            return;
        }

        // Character validation (alphanumeric + underscore only)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            $fail('Username can only contain letters, numbers, and underscores.');
            return;
        }

        // Must start with letter or underscore
        if (!preg_match('/^[a-zA-Z_]/', $value)) {
            $fail('Username must start with a letter or underscore.');
            return;
        }

        // Cannot end with underscore
        if (str_ends_with($value, '_')) {
            $fail('Username cannot end with an underscore.');
            return;
        }

        // No consecutive underscores
        if (str_contains($value, '__')) {
            $fail('Username cannot contain consecutive underscores.');
            return;
        }

        // Reserved usernames check
        if (in_array(strtolower($value), $this->reservedUsernames)) {
            $fail('This username is reserved and cannot be used.');
            return;
        }

        // Uniqueness check
        $query = User::where('username', $value);
        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }
        
        if ($query->exists()) {
            $fail('This username is already taken.');
            return;
        }
    }
}