<?php

namespace App\Http\Requests;

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
            'posts.*.content' => 'required|string|max:280',
            'media' => 'nullable|array|max:4',
            'media.*' => 'file|mimes:jpeg,png,gif,mp4|max:10240'
        ];
    }
}