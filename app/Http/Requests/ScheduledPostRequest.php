<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class ScheduledPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', new ContentLength('post')],
            'scheduled_at' => 'required|date|after:now',
            'media' => 'nullable|array|max:4',
            'media.*' => ['file', new FileUpload('media_general')],
            'poll' => 'nullable|array',
            'poll.question' => 'required_with:poll|string|max:200',
            'poll.options' => 'required_with:poll|array|min:2|max:4',
            'visibility' => 'nullable|string|in:public,followers,private'
        ];
    }
}