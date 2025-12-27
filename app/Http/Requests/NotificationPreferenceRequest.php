<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preferences' => 'required|array',
            'preferences.email' => 'required|array',
            'preferences.push' => 'required|array',
            'preferences.in_app' => 'required|array',
            'preferences.email.*' => 'boolean',
            'preferences.push.*' => 'boolean',
            'preferences.in_app.*' => 'boolean'
        ];
    }
}