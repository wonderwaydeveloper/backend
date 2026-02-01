<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) {
            $fail('The password must be at least 8 characters.');
        }
        
        // At least one letter (uppercase OR lowercase)
        if (!preg_match('/[a-zA-Z]/', $value)) {
            $fail('The password must contain at least one letter.');
        }
        
        // At least one number
        if (!preg_match('/[0-9]/', $value)) {
            $fail('The password must contain at least one number.');
        }
        
        $weakPasswords = ['password', '123456', 'qwerty', 'abc123', 'password123'];
        if (in_array(strtolower($value), $weakPasswords)) {
            $fail('The password is too weak.');
        }
    }
}
