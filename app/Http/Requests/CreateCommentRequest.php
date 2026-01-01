<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:280|min:1',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:2048',
            'gif_url' => 'nullable|url|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'content.max' => 'Comment content must not exceed 280 characters',
            'content.min' => 'Comment content cannot be empty',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image format must be jpeg, jpg, png, gif or webp',
            'image.max' => 'Image size must not exceed 2MB',
            'gif_url.url' => 'GIF URL is invalid',
            'gif_url.max' => 'GIF URL is too long',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }
}