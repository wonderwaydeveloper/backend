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
            'device_token' => 'required|string|max:' . config('content.validation.max.url'),
            'device_type' => 'required|string|in:ios,android,web',
            'app_version' => 'nullable|string|max:' . config('content.validation.max.version'),
            'title' => 'sometimes|required|string|max:' . config('content.validation.max.title'),
            'body' => 'sometimes|required|string|max:' . config('content.validation.max.text_long'),
            'data' => 'sometimes|nullable|array'
        ];
    }
}