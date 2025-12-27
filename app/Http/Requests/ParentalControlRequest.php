<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParentalControlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'child_email' => 'sometimes|required|email|exists:users,email',
            'child_id' => 'sometimes|required|integer|exists:users,id',
            'settings' => 'sometimes|required|array',
            'settings.content_filter' => 'boolean',
            'settings.time_limit' => 'integer|min:0|max:1440',
            'settings.allowed_hours' => 'array',
            'content_type' => 'sometimes|required|string|in:post,user,hashtag',
            'content_id' => 'sometimes|required|integer'
        ];
    }
}