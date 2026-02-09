<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
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
            'avatar' => ['nullable', new FileUpload('avatar')],
            'banner' => ['nullable', new FileUpload('image')],
            'rules' => 'nullable|array|max:10',
            'rules.*' => 'string|max:200',
            'settings' => 'nullable|array',
        ];
    }
}