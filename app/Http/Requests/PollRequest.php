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
            'question' => 'required|string|max:' . config('limits.polls.max_question_length', 200),
            'options' => 'required|array|min:' . config('limits.polls.min_options', 2) . '|max:' . config('limits.polls.max_options', 4),
            'options.*' => 'required|string|max:' . config('limits.polls.max_option_length', 100),
            'duration_hours' => 'required|integer|min:' . config('limits.polls.min_duration_hours', 1) . '|max:' . config('limits.polls.max_duration_hours', 168),
            'multiple_choice' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'post_id.required' => 'Post ID is required',
            'post_id.exists' => 'Post does not exist',
            'question.required' => 'Poll question is required',
            'question.max' => 'Poll question cannot exceed :max characters',
            'options.required' => 'Poll options are required',
            'options.min' => 'Poll must have at least :min options',
            'options.max' => 'Poll cannot have more than :max options',
            'options.*.required' => 'Each option must have text',
            'options.*.max' => 'Each option cannot exceed :max characters',
            'duration_hours.required' => 'Poll duration is required',
            'duration_hours.min' => 'Poll duration must be at least :min hour',
            'duration_hours.max' => 'Poll duration cannot exceed :max hours (7 days)',
        ];
    }
}