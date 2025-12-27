<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordResetRequest;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    private $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function forgot(PasswordResetRequest $request)
    {
        $validated = $request->validated();
        $user = User::where('email', $validated['email'])->first();
        $token = Str::random(60);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $this->emailService->sendPasswordResetEmail($user, $token);

        return response()->json(['message' => 'Password reset link sent to your email']);
    }

    public function reset(PasswordResetRequest $request)
    {
        $validated = $request->validated();

        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $tokenData || ! Hash::check($validated['token'], $tokenData->token)) {
            throw ValidationException::withMessages(['token' => ['Invalid token']]);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 60) {
            throw ValidationException::withMessages(['token' => ['Token expired']]);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update(['password' => Hash::make($validated['password'])]);

        \DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'valid' => false,
                'message' => 'User not found',
            ], 404);
        }

        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $tokenData) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token',
            ], 400);
        }

        if (! Hash::check($request->token, $tokenData->token)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token',
            ], 400);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 60) {
            return response()->json([
                'valid' => false,
                'message' => 'Token expired',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Token is valid',
        ]);
    }
}
