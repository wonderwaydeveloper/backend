<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:' . config('content.validation.content.community.name_max_length', 100) . '|unique:communities,name',
            'description' => 'required|string|max:' . config('content.validation.content.community.description_max_length', 500),
            'privacy' => 'required|in:public,private,restricted',
            'avatar' => ['nullable', new FileUpload('avatar')],
            'banner' => ['nullable', new FileUpload('image')],
            'rules' => 'nullable|array|max:' . config('content.validation.max.rules'),
            'rules.*' => 'string|max:' . config('content.validation.max.text_long'),
            'settings' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Community name is required',
            'name.unique' => 'Community name already exists',
            'description.required' => 'Community description is required',
            'privacy.in' => 'Privacy must be public, private, or restricted',
            'avatar.image' => 'Avatar must be an image',
            'banner.image' => 'Banner must be an image',
        ];
    }
}