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
        return [
            'content' => ['required_without_all:media,gif_url', 'nullable', new ContentLength('message')],
            'media' => ['required_without_all:content,gif_url', 'nullable', new FileUpload('media_general')],
            'gif_url' => 'required_without_all:content,media|nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'content.max' => 'Message must not exceed 1000 characters',
            'media.max' => 'File size must not exceed 10MB',
            'gif_url.url' => 'GIF URL is invalid',
        ];
    }
}