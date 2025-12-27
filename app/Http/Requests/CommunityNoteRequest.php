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
            'content' => 'required|string|min:10|max:500',
            'sources' => 'nullable|array|max:3',
            'sources.*' => 'url|max:255'
        ];
    }
}