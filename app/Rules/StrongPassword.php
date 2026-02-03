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
        
        $weakPasswords = [
            // English common passwords
            'password', '123456', 'qwerty', 'abc123', 'password123',
            'admin', 'letmein', 'welcome', 'monkey', '1234567890',
            'password1', '123123', 'qwerty123', 'dragon', 'master',
            'hello', 'login', 'princess', 'solo', 'qwertyuiop',
            'starwars', 'superman', 'iloveyou', 'trustno1',
            // Persian/Farsi common passwords
            'رمزعبور', '۱۲۳۴۵۶', 'سلام', 'ایران', 'تهران',
            'محمد', 'علی', 'فاطمه', 'مریم', 'احمد',
            'پسورد', 'کلمه', 'عبور', 'رمز'
        ];
        if (in_array(strtolower($value), $weakPasswords)) {
            $fail('The password is too weak.');
        }
    }
}
