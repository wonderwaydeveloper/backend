<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class CreateCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = $this->user();
        $maxFileSize = app(\App\Services\SubscriptionLimitService::class)->getMaxFileSize($user);
        $maxFileSizeKB = $maxFileSize / 1024;
        
        return [
            'content' => ['required', new ContentLength('comment')],
            'media' => "nullable|file|mimes:jpeg,jpg,png,gif,webp|max:{$maxFileSizeKB}",
            'gif_url' => 'nullable|url|max:' . config('validation.max.token'),
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Comment content is required',
            'content.max' => 'Comment content must not exceed 280 characters',
            'content.min' => 'Comment content cannot be empty',
            'media.file' => 'Media must be a file',
            'media.mimes' => 'Media format must be jpeg, jpg, png, gif or webp',
            'media.max' => 'Media size must not exceed 5MB',
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