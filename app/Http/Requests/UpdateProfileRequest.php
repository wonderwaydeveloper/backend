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
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'نام باید متن باشد',
            'name.max' => 'نام نباید بیشتر از 255 کاراکتر باشد',
            'name.min' => 'نام باید حداقل 2 کاراکتر باشد',
            'bio.string' => 'بیوگرافی باید متن باشد',
            'bio.max' => 'بیوگرافی نباید بیشتر از 500 کاراکتر باشد',
            'avatar.url' => 'آدرس آواتار معتبر نیست',
            'avatar.max' => 'آدرس آواتار خیلی طولانی است',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'bio' => 'بیوگرافی',
            'avatar' => 'آواتار',
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
    }
}