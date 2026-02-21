<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:' . config('content.validation.min.search') . '|max:' . config('content.validation.max.text_medium'),
            'filter' => 'nullable|in:latest,popular,media',
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'Search term is required',
            'q.min' => 'Search term must be at least 1 character',
            'q.max' => 'Search term must not exceed 100 characters',
        ];
    }
}