<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:1000',
            'media' => 'nullable|file|mimes:jpg,jpeg,png,gif,mp4,mov|max:10240',
            'gif_url' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'پیام نباید بیشتر از 1000 کاراکتر باشد',
            'media.max' => 'حجم فایل نباید بیشتر از 10MB باشد',
            'gif_url.url' => 'آدرس GIF معتبر نیست',
        ];
    }
}
