<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $maxFileSize = app(\App\Services\SubscriptionLimitService::class)->getMaxFileSize($user);
        $maxFileSizeKB = $maxFileSize / 1024;
        
        return [
            'content' => ['required_without_all:attachments,gif_url', 'nullable', new ContentLength('message')],
            'attachments' => 'required_without_all:content,gif_url|nullable|array|max:' . config('content.validation.max.attachments'),
            'attachments.*' => "file|max:{$maxFileSizeKB}",
            'gif_url' => 'required_without_all:content,attachments|nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'Message must not exceed 1000 characters',
            'attachments.max' => 'You can upload maximum 10 attachments',
            'attachments.*.max' => 'Each file must not exceed 10MB',
            'gif_url.url' => 'GIF URL is invalid',
        ];
    }
}