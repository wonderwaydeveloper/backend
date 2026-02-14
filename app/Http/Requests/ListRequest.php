<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            'privacy' => 'required|in:public,private',
            'banner_image' => 'nullable|image|max:1024'
        ];
    }
}