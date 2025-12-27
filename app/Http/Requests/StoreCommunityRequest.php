<?php

namespace App\Http\Requests;

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
            'name' => 'required|string|max:100|unique:communities,name',
            'description' => 'required|string|max:500',
            'privacy' => 'required|in:public,private,restricted',
            'avatar' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:5120',
            'rules' => 'nullable|array|max:10',
            'rules.*' => 'string|max:200',
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