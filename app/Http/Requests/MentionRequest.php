<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MentionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:' . config('content.validation.min.mention') . '|max:' . config('content.validation.max.text_short'),
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search query is required',
            'q.min' => 'Search query must be at least 2 characters',
            'q.max' => 'Search query cannot exceed 50 characters',
        ];
    }
}
