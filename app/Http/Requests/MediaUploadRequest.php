<?php

namespace App\Http\Requests;

use App\Rules\FileUpload;
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
            'image' => ['required', new FileUpload('media_general')],
            'alt_text' => 'nullable|string|max:200'
        ];
    }
}