<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpeg,png,gif,webp', 'max:5120'],
            'alt_text' => 'nullable|string|max:200',
            'type' => 'nullable|in:post,story,avatar,cover',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'تصویر الزامی است',
            'image.mimes' => 'فرمت تصویر باید jpeg, png, gif یا webp باشد',
            'image.max' => 'حداکثر حجم تصویر 5MB است',
            'alt_text.max' => 'حداکثر طول متن جایگزین 200 کاراکتر است',
            'type.in' => 'نوع تصویر نامعتبر است',
        ];
    }
}