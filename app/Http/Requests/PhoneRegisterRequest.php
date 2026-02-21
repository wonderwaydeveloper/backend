<?php

namespace App\Http\Requests;

use App\Rules\{ValidUsername, StrongPassword, MinimumAge};
use Illuminate\Foundation\Http\FormRequest;

class PhoneRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . config('content.validation.user.name.max_length', 50),
            'username' => ['required', new ValidUsername()],
            'email' => 'nullable|string|email|max:' . config('content.validation.user.email.max_length', 255) . '|unique:users',
            'phone' => 'required|string|regex:/^09[0-9]{9}$/|unique:users',
            'password' => ['required', 'string', 'min:' . config('content.validation.password.min_length', 8), 'confirmed', new StrongPassword()],
            'date_of_birth' => ['required', 'date', config('content.validation.date.before_rule', 'before:today'), new MinimumAge()],
        ];
    }
}