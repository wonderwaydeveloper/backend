<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . config('validation.max.text_short'),
            'description' => 'nullable|string|max:' . config('validation.max.text_long'),
            'privacy' => 'required|in:public,private',
            'banner_image' => 'nullable|image|max:' . config('validation.max.banner_size')
        ];
    }
}