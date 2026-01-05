<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'two_factor_code' => 'nullable|string|size:6|regex:/^[0-9]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email or username is required',
            'password.required' => 'Password is required',
            'two_factor_code.size' => 'Two-factor code must be 6 digits',
            'two_factor_code.regex' => 'Two-factor code must contain only numbers',
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => 'Email or Username',
            'password' => 'Password',
            'two_factor_code' => 'Two-factor code',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize login field
        if ($this->has('login')) {
            $this->merge([
                'login' => strtolower(trim($this->input('login'))),
            ]);
        }

        // Clean 2FA code
        if ($this->has('two_factor_code')) {
            $this->merge([
                'two_factor_code' => preg_replace('/\D/', '', $this->input('two_factor_code')),
            ]);
        }
    }
}
