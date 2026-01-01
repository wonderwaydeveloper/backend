<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
            'date_of_birth' => 'required|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required',
            'name.max' => 'Name must not exceed 255 characters',
            'username.required' => 'Username is required',
            'username.unique' => 'This username has already been taken',
            'username.regex' => 'Username can only contain letters, numbers and _',
            'email.required' => 'Email is required',
            'email.email' => 'Email format is invalid',
            'email.unique' => 'This email has already been registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must contain lowercase, uppercase, number and special character',
            'password.confirmed' => 'Password confirmation does not match',
            'date_of_birth.required' => 'Date of birth is required',
            'date_of_birth.date' => 'Date of birth format is invalid',
            'date_of_birth.before' => 'Date of birth must be before today',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'username' => 'Username',
            'email' => 'Email',
            'password' => 'Password',
            'date_of_birth' => 'Date of birth',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean and normalize data
        $this->merge([
            'name' => trim($this->input('name')),
            'username' => strtolower(trim($this->input('username'))),
            'email' => strtolower(trim($this->input('email'))),
        ]);
    }
}