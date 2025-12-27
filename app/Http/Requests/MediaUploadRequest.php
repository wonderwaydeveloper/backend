<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => 'required|file|image|mimes:jpeg,png,gif,webp|max:10240',
            'alt_text' => 'nullable|string|max:200'
        ];
    }
}