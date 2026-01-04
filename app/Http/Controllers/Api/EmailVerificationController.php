<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    public function __construct(private EmailService $emailService) {}

    public function verify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)
                   ->whereNotNull('email_verification_token')
                   ->whereNull('email_verified_at')
                   ->first();
        
        if (!$user || !Hash::check($request->code, $user->email_verification_token)) {
            return response()->json(['error' => 'Invalid or expired code'], 422);
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_token' => null
        ]);

        return response()->json(['message' => 'Email verified successfully']);
    }

    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)
                   ->whereNull('email_verified_at')
                   ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found or already verified'], 422);
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['email_verification_token' => Hash::make($code)]);

        $this->emailService->sendVerificationEmail($user, $code);

        return response()->json(['message' => 'Verification code sent']);
    }

    public function status(Request $request)
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail()
        ]);
    }
}