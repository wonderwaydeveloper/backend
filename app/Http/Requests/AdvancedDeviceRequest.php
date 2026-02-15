<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdvancedDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:mobile,desktop,tablet',
            'browser' => 'nullable|string|max:100',
            'os' => 'nullable|string|max:100',
            'push_token' => 'nullable|string|max:500',
        ];
    }
}
