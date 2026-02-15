<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => 'required|string|max:500',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
        ];
    }
}
