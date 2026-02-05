<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureEmail implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Basic email validation
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('The :attribute must be a valid email address.');
            return;
        }

        // Check against blacklisted domains
        $blacklist = config('authentication.email.blacklist_domains', []);
        $domain = substr(strrchr($value, '@'), 1);
        
        if (in_array($domain, $blacklist)) {
            $fail('The :attribute domain is not allowed.');
            return;
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousPatterns($value)) {
            $fail('The :attribute contains invalid characters.');
            return;
        }

        // Check for disposable email domains (basic check)
        if ($this->isDisposableEmail($domain)) {
            $fail('Disposable email addresses are not allowed.');
            return;
        }
    }

    private function hasSuspiciousPatterns(string $email): bool
    {
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/[<>"\']/',           // HTML/Script injection
            '/javascript:/i',      // JavaScript protocol
            '/data:/i',           // Data protocol
            '/\s/',               // Whitespace
            '/[^\x20-\x7E]/',     // Non-printable characters
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }

        return false;
    }

    private function isDisposableEmail(string $domain): bool
    {
        // Common disposable email domains
        $disposableDomains = [
            '10minutemail.com',
            'tempmail.org',
            'guerrillamail.com',
            'mailinator.com',
            'yopmail.com',
            'temp-mail.org',
            'throwaway.email',
        ];

        return in_array(strtolower($domain), $disposableDomains);
    }
}