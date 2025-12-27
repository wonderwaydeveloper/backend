<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('communities', 'name')->ignore($this->community->id)
            ],
            'description' => 'sometimes|string|max:500',
            'privacy' => 'sometimes|in:public,private,restricted',
            'avatar' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:5120',
            'rules' => 'nullable|array|max:10',
            'rules.*' => 'string|max:200',
            'settings' => 'nullable|array',
        ];
    }
}