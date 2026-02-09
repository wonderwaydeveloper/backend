<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', new ContentLength('post')],
            'image' => ['nullable', new FileUpload('avatar')],
            'video' => ['nullable', new FileUpload('video')],
            'gif_url' => 'nullable|url|max:500',
            'reply_settings' => 'nullable|in:everyone,following,mentioned,none',
            'quoted_post_id' => 'nullable|exists:posts,id',
            'is_draft' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Post content is required',
            'content.max' => 'Post content must not exceed 280 characters',
            'image.image' => 'File must be an image',
            'image.mimes' => 'Image format must be jpeg, jpg, png, gif or webp',
            'image.max' => 'Image size must not exceed 2MB',
            'video.file' => 'Video file is invalid',
            'video.mimes' => 'Video format must be mp4, mov, avi, mkv or webm',
            'video.max' => 'Video size must not exceed 100MB',
            'gif_url.url' => 'GIF URL is invalid',
            'gif_url.max' => 'GIF URL is too long',
            'reply_settings.in' => 'Reply settings is invalid',
            'quoted_post_id.exists' => 'Quoted post does not exist',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'content' => 'Post content',
            'image' => 'Image',
            'gif_url' => 'GIF URL',
            'reply_settings' => 'Reply settings',
            'quoted_post_id' => 'Quoted post',
            'is_draft' => 'Draft mode',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean content from extra spaces
        if ($this->has('content')) {
            $this->merge([
                'content' => trim($this->input('content')),
            ]);
        }

        // Set default reply settings
        if (! $this->has('reply_settings')) {
            $this->merge(['reply_settings' => 'everyone']);
        }
    }
}