<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PhoneVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'date_of_birth' => ['required', 'date', 'before:today', new \App\Rules\MinimumAge()],
            'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/|min:10|max:15',
            'country_code' => 'nullable|string|max:5'
        ];
    }
}