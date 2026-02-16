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
            'name' => 'required|string|max:' . config('validation.max.url'),
            'type' => 'required|in:mobile,desktop,tablet',
            'browser' => 'nullable|string|max:' . config('validation.max.text_medium'),
            'os' => 'nullable|string|max:' . config('validation.max.text_medium'),
            'push_token' => 'nullable|string|max:' . config('validation.max.token'),
        ];
    }
}
