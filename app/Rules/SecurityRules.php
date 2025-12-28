<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SecureContent implements Rule
{
    private array $maliciousPatterns = [
        // XSS patterns
        '/<script[^>]*>.*?<\/script>/is',
        '/<iframe[^>]*>.*?<\/iframe>/is',
        '/<object[^>]*>.*?<\/object>/is',
        '/<embed[^>]*>/i',
        '/<applet[^>]*>/i',
        '/javascript:/i',
        '/vbscript:/i',
        '/data:text\/html/i',
        '/on\w+\s*=/i',
        '/expression\s*\(/i',
        
        // SQL injection patterns
        '/(\bUNION\b.*\bSELECT\b)/i',
        '/(\bDROP\b.*\bTABLE\b)/i',
        '/(\bINSERT\b.*\bINTO\b)/i',
        '/(\bDELETE\b.*\bFROM\b)/i',
        '/(\bUPDATE\b.*\bSET\b)/i',
        '/(\'\s*(OR|AND)\s*\'?\d+\'?\s*=\s*\'?\d+)/i',
        '/(\"\s*(OR|AND)\s*\"?\d+\"?\s*=\s*\"?\d+)/i',
        
        // File inclusion patterns
        '/\.\.[\/\\\\]/i',
        '/etc\/passwd/i',
        '/proc\/self\/environ/i',
        '/\/windows\/system32/i',
        '/boot\.ini/i',
        
        // Command injection patterns
        '/[;&|`$(){}[\]]/i',
        '/\b(exec|system|shell_exec|passthru|eval)\s*\(/i',
        
        // PHP code patterns
        '/<\?php/i',
        '/<\?=/i',
        '/<\?\s/i',
        '/<%/i',
    ];
    
    private string $attribute;
    private string $failedPattern = '';
    
    public function passes($attribute, $value): bool
    {
        $this->attribute = $attribute;
        
        if (!is_string($value)) {
            return true; // Let other validation rules handle non-strings
        }
        
        // Check for malicious patterns
        foreach ($this->maliciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                $this->failedPattern = $pattern;
                return false;
            }
        }
        
        // Check for suspicious character sequences
        if ($this->hasSuspiciousCharacters($value)) {
            return false;
        }
        
        // Check for encoded attacks
        if ($this->hasEncodedAttacks($value)) {
            return false;
        }
        
        return true;
    }
    
    private function hasSuspiciousCharacters(string $value): bool
    {
        // Check for excessive special characters
        $specialChars = preg_match_all('/[<>"\'\(\)\{\}\[\];]/', $value);
        if ($specialChars > strlen($value) * 0.1) { // More than 10% special chars
            return true;
        }
        
        // Check for null bytes
        if (strpos($value, "\0") !== false) {
            return true;
        }
        
        // Check for control characters
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            return true;
        }
        
        return false;
    }
    
    private function hasEncodedAttacks(string $value): bool
    {
        // URL decode and check again
        $decoded = urldecode($value);
        if ($decoded !== $value) {
            foreach ($this->maliciousPatterns as $pattern) {
                if (preg_match($pattern, $decoded)) {
                    return true;
                }
            }
        }
        
        // HTML entity decode and check
        $htmlDecoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
        if ($htmlDecoded !== $value) {
            foreach ($this->maliciousPatterns as $pattern) {
                if (preg_match($pattern, $htmlDecoded)) {
                    return true;
                }
            }
        }
        
        // Base64 decode check (if it looks like base64)
        if (preg_match('/^[A-Za-z0-9+\/]+=*$/', $value) && strlen($value) % 4 === 0) {
            $base64Decoded = base64_decode($value, true);
            if ($base64Decoded !== false) {
                foreach ($this->maliciousPatterns as $pattern) {
                    if (preg_match($pattern, $base64Decoded)) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function message(): string
    {
        return "The {$this->attribute} contains potentially malicious content.";
    }
}

class SecureFilename implements Rule
{
    private array $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp',
        'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar',
        'sh', 'py', 'pl', 'rb', 'cgi', 'htaccess'
    ];
    
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        // Check for dangerous extensions
        $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (in_array($extension, $this->dangerousExtensions)) {
            return false;
        }
        
        // Check for null bytes
        if (strpos($value, "\0") !== false) {
            return false;
        }
        
        // Check for path traversal
        if (strpos($value, '..') !== false) {
            return false;
        }
        
        // Check for suspicious patterns
        $suspiciousPatterns = [
            '/\.(php|asp|jsp)\./i', // Double extensions
            '/\.(php|asp|jsp)$/i',  // Direct dangerous extensions
            '/[<>:"|?*]/',          // Invalid filename characters
            '/^(con|prn|aux|nul|com[1-9]|lpt[1-9])$/i' // Windows reserved names
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function message(): string
    {
        return 'The filename contains dangerous or invalid characters.';
    }
}

class SecureUrl implements Rule
{
    private array $allowedSchemes = ['http', 'https'];
    private array $blockedDomains = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1'
    ];
    
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        // Parse URL
        $parsed = parse_url($value);
        if ($parsed === false) {
            return false;
        }
        
        // Check scheme
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], $this->allowedSchemes)) {
            return false;
        }
        
        // Check for blocked domains
        if (isset($parsed['host'])) {
            $host = strtolower($parsed['host']);
            
            // Check blocked domains
            foreach ($this->blockedDomains as $blocked) {
                if ($host === $blocked || str_ends_with($host, '.' . $blocked)) {
                    return false;
                }
            }
            
            // Check for private IP ranges
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                if (!filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    public function message(): string
    {
        return 'The URL is not allowed or contains suspicious content.';
    }
}

class StrongPassword implements Rule
{
    private int $minLength;
    private bool $requireUppercase;
    private bool $requireLowercase;
    private bool $requireNumbers;
    private bool $requireSpecialChars;
    
    public function __construct(
        int $minLength = 8,
        bool $requireUppercase = true,
        bool $requireLowercase = true,
        bool $requireNumbers = true,
        bool $requireSpecialChars = true
    ) {
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireLowercase = $requireLowercase;
        $this->requireNumbers = $requireNumbers;
        $this->requireSpecialChars = $requireSpecialChars;
    }
    
    public function passes($attribute, $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        // Check minimum length
        if (strlen($value) < $this->minLength) {
            return false;
        }
        
        // Check for uppercase letters
        if ($this->requireUppercase && !preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        // Check for lowercase letters
        if ($this->requireLowercase && !preg_match('/[a-z]/', $value)) {
            return false;
        }
        
        // Check for numbers
        if ($this->requireNumbers && !preg_match('/[0-9]/', $value)) {
            return false;
        }
        
        // Check for special characters
        if ($this->requireSpecialChars && !preg_match('/[^A-Za-z0-9]/', $value)) {
            return false;
        }
        
        // Check against common passwords
        if ($this->isCommonPassword($value)) {
            return false;
        }
        
        return true;
    }
    
    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey',
            '1234567890', 'password1', '123123', 'qwerty123'
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    public function message(): string
    {
        $requirements = [];
        
        if ($this->requireUppercase) $requirements[] = 'uppercase letters';
        if ($this->requireLowercase) $requirements[] = 'lowercase letters';
        if ($this->requireNumbers) $requirements[] = 'numbers';
        if ($this->requireSpecialChars) $requirements[] = 'special characters';
        
        $reqText = implode(', ', $requirements);
        
        return "Password must be at least {$this->minLength} characters and contain {$reqText}.";
    }
}