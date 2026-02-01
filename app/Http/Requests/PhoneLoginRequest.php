<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhoneLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^09[0-9]{9}$/',
            'verification_code' => 'required|string|size:6|regex:/^[0-9]+$/'
        ];
    }
}