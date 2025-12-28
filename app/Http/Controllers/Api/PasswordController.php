<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PasswordSecurityService;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    private PasswordSecurityService $passwordService;
    
    public function __construct(PasswordSecurityService $passwordService)
    {
        $this->passwordService = $passwordService;
    }
    
    public function change(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ]);
        
        $user = $request->user();
        
        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect']
            ]);
        }
        
        try {
            $this->passwordService->updatePassword($user, $request->new_password);
            
            return response()->json([
                'message' => 'Password changed successfully',
                'password_strength' => $this->passwordService->getPasswordStrengthScore($request->new_password)
            ]);
            
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'new_password' => [$e->getMessage()]
            ]);
        }
    }
    
    public function checkStrength(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);
        
        $score = $this->passwordService->getPasswordStrengthScore($request->password);
        $errors = $this->passwordService->validatePasswordStrength($request->password);
        
        return response()->json([
            'score' => $score,
            'strength' => $this->getStrengthLabel($score),
            'errors' => $errors,
            'valid' => empty($errors)
        ]);
    }
    
    public function checkExpiry(Request $request)
    {
        $user = $request->user();
        $isExpired = $this->passwordService->isPasswordExpired($user);
        
        return response()->json([
            'expired' => $isExpired,
            'last_changed' => $user->password_changed_at?->toISOString(),
            'can_change' => $this->passwordService->canChangePassword($user->id)
        ]);
    }
    
    private function getStrengthLabel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'Very Strong',
            $score >= 60 => 'Strong', 
            $score >= 40 => 'Medium',
            $score >= 20 => 'Weak',
            default => 'Very Weak'
        };
    }
}