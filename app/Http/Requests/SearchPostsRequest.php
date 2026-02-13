<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchPostsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => 'required|string|min:' . config('validation.search.query.min_length') . '|max:' . config('validation.search.query.max_length'),
            'page' => 'nullable|integer|min:1',
            'user_id' => 'nullable|integer|exists:users,id',
            'has_media' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'min_likes' => 'nullable|integer|min:0',
            'hashtags' => 'nullable|array',
            'hashtags.*' => 'string|max:50',
            'sort' => 'nullable|in:relevance,latest,oldest,popular',
        ];
    }
}
