<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PostContentRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen(trim($value)) < 1) {
            $fail('پست نمیتواند خالی باشد.');
            return;
        }

        if (strlen($value) > 280) {
            $fail('پست نمیتواند بیشتر از 280 کاراکتر باشد.');
            return;
        }

        $linkCount = preg_match_all('/https?:\/\/[^\s]+/', $value);
        if ($linkCount > 2) {
            $fail('پست نمیتواند بیشتر از 2 لینک داشته باشد.');
            return;
        }

        $mentionCount = preg_match_all('/@[a-zA-Z0-9_]+/', $value);
        if ($mentionCount > 5) {
            $fail('پست نمیتواند بیشتر از 5 منشن داشته باشد.');
            return;
        }

        if ($this->containsSpamPatterns($value)) {
            $fail('محتوای پست مشکوک به اسپم است.');
            return;
        }
    }

    private function containsSpamPatterns(string $content): bool
    {
        $spamPatterns = [
            '/(.)\1{10,}/',
            '/\b(buy|sale|discount|offer|free|win|prize)\b.*\b(now|today|click|link)\b/i',
            '/\b(follow|like|subscribe)\b.*\b(back|return|exchange)\b/i',
        ];

        foreach ($spamPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }
}