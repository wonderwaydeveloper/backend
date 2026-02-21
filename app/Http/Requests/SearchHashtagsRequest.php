<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchHashtagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:' . config('content.validation.min.search') . '|max:' . config('content.validation.max.text_short'),
            'page' => 'nullable|integer|min:1',
            'min_posts' => 'nullable|integer|min:0',
            'sort' => 'nullable|in:relevance,popular,recent',
        ];
    }
}
