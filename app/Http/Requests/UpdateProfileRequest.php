<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255|min:2',
            'bio' => 'sometimes|nullable|string|max:500',
            'avatar' => 'sometimes|nullable|string|url|max:255',
            'cover' => 'sometimes|nullable|string|url|max:255',
            'location' => 'sometimes|nullable|string|max:100',
            'website' => 'sometimes|nullable|url|max:255',
            'date_of_birth' => 'sometimes|nullable|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be text',
            'name.max' => 'Name must not exceed 255 characters',
            'name.min' => 'Name must be at least 2 characters',
            'bio.string' => 'Bio must be text',
            'bio.max' => 'Bio must not exceed 500 characters',
            'avatar.url' => 'Avatar URL is invalid',
            'cover.url' => 'Cover URL is invalid',
            'location.max' => 'Location must not exceed 100 characters',
            'website.url' => 'Website URL is invalid',
            'date_of_birth.date' => 'Date of birth is invalid',
            'date_of_birth.before' => 'Date of birth must be before today',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Name',
            'bio' => 'Bio',
            'avatar' => 'Avatar',
            'cover' => 'Cover',
            'location' => 'Location',
            'website' => 'Website',
            'date_of_birth' => 'Date of birth',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Clean and trim data
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if ($this->has('bio')) {
            $this->merge(['bio' => trim($this->input('bio'))]);
        }

        if ($this->has('location')) {
            $this->merge(['location' => trim($this->input('location'))]);
        }
    }
}