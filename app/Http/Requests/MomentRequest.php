<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MomentRequest extends FormRequest
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
            'privacy' => 'nullable|string|in:public,private',
            'post_ids' => 'nullable|array|min:2|max:10',
            'post_ids.*' => 'exists:posts,id',
            'cover_image' => 'nullable|image|max:2048',
            'is_featured' => 'boolean',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:50'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Title is required',
            'title.max' => 'Title must not exceed 100 characters',
            'cover_image.image' => 'File must be an image',
            'cover_image.max' => 'Image size must not exceed 2MB'
        ];
    }
}