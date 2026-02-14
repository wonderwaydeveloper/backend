<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:300',
            'privacy' => 'nullable|in:public,followers,invited',
            'max_participants' => 'nullable|integer|min:2|max:100',
            'scheduled_at' => 'nullable|date|after:now',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:30'
        ];
    }
}