<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StreamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'category' => 'required|string|in:gaming,music,talk,education,entertainment',
            'is_private' => 'boolean',
            'max_viewers' => 'nullable|integer|min:1|max:10000',
            'thumbnail' => 'nullable|image|max:1024',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:30'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'عنوان استریم الزامی است',
            'category.required' => 'دسته‌بندی الزامی است',
            'category.in' => 'دسته‌بندی انتخابی معتبر نیست',
            'max_viewers.max' => 'حداکثر تعداد بیننده 10000 نفر است'
        ];
    }
}