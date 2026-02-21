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
            'posts' => 'required|array|min:' . config('content.validation.min.thread_posts') . '|max:' . config('content.validation.max.array_large'),
            'posts.*.content' => ['required', new ContentLength('post')],
            'media' => 'nullable|array|max:' . config('content.validation.max.media'),
            'media.*' => ['file', new FileUpload('media_general')]
        ];
    }
}