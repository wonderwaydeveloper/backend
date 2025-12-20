<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:280',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'gif_url' => 'nullable|url',
            'reply_settings' => 'nullable|in:everyone,following,mentioned,none',
            'is_draft' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'محتوای پست الزامی است',
            'content.max' => 'محتوای پست نباید بیشتر از 280 کاراکتر باشد',
            'image.image' => 'فایل باید تصویر باشد',
            'image.max' => 'حجم تصویر نباید بیشتر از 2MB باشد',
        ];
    }
}
