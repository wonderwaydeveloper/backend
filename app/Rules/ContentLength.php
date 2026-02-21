<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ContentLength implements ValidationRule
{
    private string $type;
    
    public function __construct(string $type = 'post')
    {
        $this->type = $type;
    }
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) return;
        
        $maxLength = config("content.validation.content.{$this->type}.max_length");
        
        if (!$maxLength) {
            $fail("Invalid content type: {$this->type}");
            return;
        }
        
        $length = strlen(trim($value));
        
        if ($length < 1) {
            $fail(ucfirst($this->type) . ' cannot be empty');
            return;
        }
        
        if ($length > $maxLength) {
            $fail(ucfirst($this->type) . " cannot exceed {$maxLength} characters");
            return;
        }
        
        // Additional validations for posts
        if ($this->type === 'post') {
            $this->validatePostSpecific($value, $fail);
        }
    }
    
    private function validatePostSpecific(string $value, Closure $fail): void
    {
        $maxLinks = config('content.validation.content.post.max_links', 2);
        $maxMentions = config('content.validation.content.post.max_mentions', 5);
        
        $linkCount = preg_match_all('/https?:\/\/[^\s]+/', $value);
        if ($linkCount > $maxLinks) {
            $fail("Post cannot contain more than {$maxLinks} links");
            return;
        }
        
        $mentionCount = preg_match_all('/@[a-zA-Z0-9_]+/', $value);
        if ($mentionCount > $maxMentions) {
            $fail("Post cannot contain more than {$maxMentions} mentions");
            return;
        }
    }
}