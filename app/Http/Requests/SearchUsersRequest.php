<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:' . config('validation.min.search') . '|max:' . config('validation.max.text_short'),
            'page' => 'nullable|integer|min:1',
            'verified' => 'nullable|boolean',
            'min_followers' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:' . config('validation.max.text_medium'),
            'sort' => 'nullable|in:relevance,followers,newest',
        ];
    }
}
