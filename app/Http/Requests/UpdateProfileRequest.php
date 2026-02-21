<?php

namespace App\Http\Requests;

use App\Rules\{ValidUsername, FileUpload, MinimumAge};
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:' . config('content.validation.user.name.max_length', 50) . '|min:1',
            'username' => ['sometimes', new ValidUsername(auth()->id())],
            'bio' => 'sometimes|nullable|string|max:' . config('content.validation.user.bio.max_length', 500),
            'avatar' => ['sometimes', 'nullable', new FileUpload('avatar')],
            'cover' => ['sometimes', 'nullable', new FileUpload('image')],
            'location' => 'sometimes|nullable|string|max:' . config('content.validation.user.location.max_length', 100),
            'website' => 'sometimes|nullable|url|max:' . config('content.validation.user.website.max_length', 255),
            'date_of_birth' => ['sometimes', 'nullable', 'date', config('content.validation.date.before_rule', 'before:today'), new MinimumAge()],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be text',
            'name.max' => 'Name must not exceed 50 characters',
            'name.min' => 'Name is required',
            'username.string' => 'Username must be text',
            'username.max' => 'Username must not exceed 15 characters',
            'username.unique' => 'Username is already taken',
            'username.regex' => 'Username must be 4-15 characters, start with letter/underscore, contain only letters, numbers, and underscores',
            'bio.string' => 'Bio must be text',
            'bio.max' => 'Bio must not exceed 500 characters',
            'avatar.image' => 'Avatar must be an image',
            'avatar.mimes' => 'Avatar must be jpeg, png, jpg or gif',
            'avatar.max' => 'Avatar must not exceed 5MB',
            'cover.image' => 'Cover must be an image',
            'cover.mimes' => 'Cover must be jpeg, png, jpg or gif',
            'cover.max' => 'Cover must not exceed 5MB',
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
            'username' => 'Username',
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