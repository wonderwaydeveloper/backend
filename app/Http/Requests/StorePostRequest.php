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
            'media_ids' => 'nullable|array|max:' . config('content.validation.max.media'),
            'media_ids.*' => 'exists:media,id',
            'gif_url' => 'nullable|url|max:' . config('content.validation.max.token'),
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
            'media_ids.array' => 'Media IDs must be an array',
            'media_ids.max' => 'You can attach maximum 4 media files',
            'media_ids.*.exists' => 'Media file not found',
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
            'media_ids' => 'Media files',
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