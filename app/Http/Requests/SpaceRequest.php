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
            'title' => 'required|string|max:' . config('validation.max.title'),
            'description' => 'nullable|string|max:' . config('validation.max.content'),
            'privacy' => 'nullable|in:public,followers,invited',
            'max_participants' => 'nullable|integer|min:' . config('validation.min.participants') . '|max:' . config('validation.max.participants'),
            'scheduled_at' => 'nullable|date|after:now',
            'tags' => 'nullable|array|max:' . config('validation.max.tags'),
            'tags.*' => 'string|max:' . config('validation.max.text_short')
        ];
    }
}