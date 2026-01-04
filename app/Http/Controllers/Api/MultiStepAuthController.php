<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MultiStepAuthController extends Controller
{
    public function __construct(
        private EmailService $emailService,
        private SmsService $smsService
    ) {}

    public function step1(Request $request)
    {
        $request->validate([
            'contact' => 'required|string',
            'contact_type' => 'required|in:email,phone'
        ]);

        $sessionId = Str::uuid();
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Check if contact exists
        $exists = User::where($request->contact_type, $request->contact)->exists();
        if ($exists) {
            return response()->json(['error' => 'Contact already registered'], 422);
        }

        // Store session data
        Cache::put("registration:{$sessionId}", [
            'contact' => $request->contact,
            'contact_type' => $request->contact_type,
            'code' => $code,
            'step' => 1,
            'verified' => false
        ], now()->addMinutes(15));

        // Send verification code
        if ($request->contact_type === 'email') {
            $this->emailService->sendVerificationEmail((object)['email' => $request->contact], $code);
        } else {
            $this->smsService->sendVerificationCode($request->contact, $code);
        }

        return response()->json([
            'session_id' => $sessionId,
            'message' => 'Verification code sent',
            'next_step' => 2
        ]);
    }

    public function step2(Request $request)
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'code' => 'required|string|size:6'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 1) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        if ($session['code'] !== $request->code) {
            return response()->json(['error' => 'Invalid code'], 422);
        }

        // Mark as verified
        $session['verified'] = true;
        $session['step'] = 2;
        Cache::put("registration:{$request->session_id}", $session, now()->addMinutes(15));

        return response()->json([
            'message' => 'Contact verified',
            'next_step' => 3
        ]);
    }

    public function step3(Request $request)
    {
        $request->validate([
            'session_id' => 'required|uuid',
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'date_of_birth' => 'required|date|before:today'
        ]);

        $session = Cache::get("registration:{$request->session_id}");
        if (!$session || $session['step'] !== 2 || !$session['verified']) {
            return response()->json(['error' => 'Invalid session'], 422);
        }

        // Create user
        $userData = [
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'date_of_birth' => $request->date_of_birth,
            $session['contact_type'] => $session['contact']
        ];

        if ($session['contact_type'] === 'email') {
            $userData['email_verified_at'] = now();
        } else {
            $userData['phone_verified_at'] = now();
        }

        $user = User::create($userData);
        
        // Assign role safely
        try {
            $user->assignRole('user');
        } catch (\Exception $e) {
            // Role might not exist, continue without role
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Clear session
        Cache::forget("registration:{$request->session_id}");

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration completed'
        ], 201);
    }
}