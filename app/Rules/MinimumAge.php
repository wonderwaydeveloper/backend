<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class MinimumAge implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minimumAge = config('age_restrictions.minimum_age', 15);
        
        try {
            $birthDate = Carbon::parse($value);
            $age = $birthDate->age;
            
            if ($age < $minimumAge) {
                $fail("You must be at least {$minimumAge} years old to register.");
            }
        } catch (\Exception $e) {
            $fail('Invalid date format.');
        }
    }
}