<?php

namespace App\Http\Requests;

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
            'content' => 'required|string|max:280',
            'scheduled_at' => 'required|date|after:now',
            'media' => 'nullable|array|max:4',
            'media.*' => 'file|mimes:jpeg,png,gif,mp4|max:10240',
            'poll' => 'nullable|array',
            'poll.question' => 'required_with:poll|string|max:200',
            'poll.options' => 'required_with:poll|array|min:2|max:4',
            'visibility' => 'nullable|string|in:public,followers,private'
        ];
    }
}