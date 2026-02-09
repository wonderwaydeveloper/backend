<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FileUpload implements ValidationRule
{
    private string $type;
    
    public function __construct(string $type = 'image')
    {
        $this->type = $type;
    }
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$value) return;
        
        $config = config("validation.file_upload.{$this->type}");
        
        if (!$config) {
            $fail("Invalid file upload type: {$this->type}");
            return;
        }
        
        // Size validation
        if ($value->getSize() > ($config['max_size_kb'] * 1024)) {
            $maxSizeMB = $config['max_size_kb'] / 1024;
            $fail("File size must not exceed {$maxSizeMB}MB");
            return;
        }
        
        // MIME type validation
        $allowedMimes = explode(',', $config['allowed_mimes']);
        $extension = strtolower($value->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedMimes)) {
            $fail("File must be: " . implode(', ', $allowedMimes));
            return;
        }
    }
}