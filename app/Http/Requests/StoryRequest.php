<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'nullable|string|max:200',
            'media' => 'required|file|mimes:jpeg,png,gif,mp4,mov|max:20480',
            'duration' => 'nullable|integer|min:5|max:30',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_style' => 'nullable|string|in:normal,bold,italic',
            'is_close_friends' => 'boolean'
        ];
    }
}