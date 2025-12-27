<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PushNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_token' => 'required|string|max:255',
            'device_type' => 'required|string|in:ios,android,web',
            'app_version' => 'nullable|string|max:20',
            'title' => 'sometimes|required|string|max:100',
            'body' => 'sometimes|required|string|max:200',
            'data' => 'sometimes|nullable|array'
        ];
    }
}