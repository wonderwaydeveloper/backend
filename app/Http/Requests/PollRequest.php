<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'post_id' => 'required|exists:posts,id',
            'question' => 'required|string|max:200',
            'options' => 'required|array|min:2|max:4',
            'options.*' => 'required|string|max:100',
            'duration_hours' => 'required|integer|min:1|max:168',
            'multiple_choice' => 'boolean'
        ];
    }
}