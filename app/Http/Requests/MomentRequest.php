<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
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
            'title' => 'required|string|max:' . config('validation.max.title'),
            'description' => 'nullable|string|max:' . config('validation.max.description'),
            'privacy' => 'nullable|string|in:public,private',
            'post_ids' => 'nullable|array|min:' . config('validation.min.moment_posts') . '|max:' . config('validation.max.array_medium'),
            'post_ids.*' => 'exists:posts,id',
            'cover_image' => ['nullable', new FileUpload('avatar')],
            'is_featured' => 'boolean',
            'tags' => 'nullable|array|max:' . config('validation.max.tags'),
            'tags.*' => 'string|max:' . config('validation.max.text_short')
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