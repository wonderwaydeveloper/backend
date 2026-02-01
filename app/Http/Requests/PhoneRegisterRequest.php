<?php

namespace App\Http\Requests;

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
            'name' => 'required|string|max:50',
            'username' => ['required', 'string', 'max:15', 'unique:users', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]{3,14}$/'],
            'email' => 'nullable|string|email|max:255|unique:users',
            'phone' => 'required|string|regex:/^09[0-9]{9}$/|unique:users',
            'password' => ['required', 'string', 'min:8', 'confirmed', new \App\Rules\StrongPassword()],
            'date_of_birth' => ['required', 'date', 'before:today', new \App\Rules\MinimumAge()],
        ];
    }
}