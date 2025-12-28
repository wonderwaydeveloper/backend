<?php

namespace App\Services;

class ContentSanitizationService
{
    private array $allowedTags = ['b', 'i', 'u', 'strong', 'em', 'br', 'p'];
    
    public function sanitizeHtml(string $content): string
    {
        // Remove all HTML tags except allowed ones
        $content = strip_tags($content, '<' . implode('><', $this->allowedTags) . '>');
        
        // Remove dangerous attributes
        $content = preg_replace('/\s(on\w+|style|class|id)\s*=\s*["\'][^"\']*["\']/i', '', $content);
        
        // Remove javascript: and data: URLs
        $content = preg_replace('/\b(javascript|data|vbscript):/i', '', $content);
        
        return $content;
    }
    
    public function sanitizeText(string $content): string
    {
        // Remove null bytes
        $content = str_replace("\0", '', $content);
        
        // Remove control characters except newlines and tabs
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
        
        // Normalize whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        
        return trim($content);
    }
    
    public function detectMaliciousContent(string $content): array
    {
        $threats = [];
        
        // SQL injection patterns
        if (preg_match('/(union|select|insert|delete|update|drop)\s+/i', $content)) {
            $threats[] = 'SQL injection attempt';
        }
        
        // XSS patterns
        if (preg_match('/<script|javascript:|on\w+\s*=/i', $content)) {
            $threats[] = 'XSS attempt';
        }
        
        // File inclusion patterns
        if (preg_match('/\.\.[\/\\\\]|\/etc\/|\/proc\/|\/var\//i', $content)) {
            $threats[] = 'Path traversal attempt';
        }
        
        return $threats;
    }
}