<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:500',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'نام نباید بیشتر از 255 کاراکتر باشد',
            'bio.max' => 'بیو نباید بیشتر از 500 کاراکتر باشد',
            'avatar.image' => 'فایل باید تصویر باشد',
            'avatar.max' => 'حجم تصویر نباید بیشتر از 2MB باشد',
        ];
    }
}
