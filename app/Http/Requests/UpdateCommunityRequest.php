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
                'max:' . config('content.validation.max.name'),
                Rule::unique('communities', 'name')->ignore($this->community->id)
            ],
            'description' => 'sometimes|string|max:' . config('content.validation.max.description'),
            'privacy' => 'sometimes|in:public,private,restricted',
            'avatar' => ['nullable', new FileUpload('avatar')],
            'banner' => ['nullable', new FileUpload('image')],
            'rules' => 'nullable|array|max:' . config('content.validation.max.rules'),
            'rules.*' => 'string|max:' . config('content.validation.max.text_long'),
            'settings' => 'nullable|array',
        ];
    }
}