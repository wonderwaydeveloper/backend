<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommunityNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|min:' . config('content.validation.min.community_note') . '|max:' . config('content.validation.max.description'),
            'sources' => 'nullable|array|max:' . config('content.validation.max.sources'),
            'sources.*' => 'url|max:' . config('content.validation.max.url')
        ];
    }
}