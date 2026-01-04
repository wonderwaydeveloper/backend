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
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            ['token' => Hash::make($code), 'created_at' => now()]
        );

        $this->emailService->sendPasswordResetEmail($user, $code);

        return response()->json(['message' => 'Password reset code sent to your email']);
    }

    public function reset(PasswordResetRequest $request)
    {
        $validated = $request->validated();

        $tokenData = \DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->first();

        if (! $tokenData || ! Hash::check($validated['code'], $tokenData->token)) {
            throw ValidationException::withMessages(['code' => ['Invalid code']]);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 15) {
            throw ValidationException::withMessages(['code' => ['Code expired']]);
        }

        $user = User::where('email', $validated['email'])->first();
        $user->update(['password' => Hash::make($validated['password'])]);

        \DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json(['message' => 'Password reset successfully']);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
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
                'message' => 'Invalid code',
            ], 400);
        }

        if (! Hash::check($request->code, $tokenData->token)) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid code',
            ], 400);
        }

        if (now()->diffInMinutes($tokenData->created_at) > 15) {
            return response()->json([
                'valid' => false,
                'message' => 'Code expired',
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Code is valid',
        ]);
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
