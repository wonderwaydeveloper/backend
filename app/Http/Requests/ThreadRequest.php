<?php

namespace App\Http\Requests;

use App\Rules\{ContentLength, FileUpload};
use Illuminate\Foundation\Http\FormRequest;

class ThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'posts' => 'required|array|min:2|max:25',
            'posts.*.content' => ['required', new ContentLength('post')],
            'media' => 'nullable|array|max:4',
            'media.*' => ['file', new FileUpload('media_general')]
        ];
    }
}