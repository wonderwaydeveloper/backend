<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'content' => ['required', new ContentLength('post')],
            'edit_reason' => 'nullable|string|max:' . config('content.validation.max.text_medium'),
            'image' => ['nullable', new FileUpload('avatar')],
            'gif_url' => 'nullable|url|max:' . config('content.validation.max.token'),
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Post content is required',
            'content.max' => 'Post content must not exceed 280 characters',
            'content.min' => 'Post content cannot be empty',
            'edit_reason.max' => 'Edit reason must not exceed 100 characters',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image format must be jpeg, jpg, png, gif or webp',
            'image.max' => 'Image size must not exceed 2MB',
            'gif_url.url' => 'GIF URL is invalid',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('content')) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }
}