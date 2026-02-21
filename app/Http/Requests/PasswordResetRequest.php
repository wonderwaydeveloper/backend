<?php

namespace App\Http\Requests;

use App\Rules\StrongPassword;
use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email', // Removed exists validation to prevent enumeration
            'token' => 'sometimes|required|string',
            'code' => 'sometimes|required|string|size:6',
            'password' => ['sometimes', 'required', 'string', 'min:' . config('content.validation.password.min_length', 8), 'confirmed', new StrongPassword()]
        ];
    }
}