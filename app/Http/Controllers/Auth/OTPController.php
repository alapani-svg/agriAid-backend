<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendLoginOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OTPController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required'],
            'purpose' => ['nullable', 'in:login,password_reset,email_verification,phone_verification'],
        ]);

        $purpose = $data['purpose'] ?? 'login';
        $user = User::find($data['user_id']);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $code = (string) random_int(100000, 999999);
        $key = "otp:{$purpose}:{$user->id}";
        Cache::put($key, $code, now()->addMinutes(10));

        $user->notify(new SendLoginOtpNotification($code, $purpose));

        return response()->json([
            'message' => 'OTP generated and sent successfully',
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required'],
            'code' => ['required', 'digits:6'],
            'purpose' => ['nullable', 'in:login,password_reset,email_verification,phone_verification'],
        ]);

        $purpose = $data['purpose'] ?? 'login';
        $user = User::find($data['user_id']);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $key = "otp:{$purpose}:{$user->id}";
        $expected = Cache::get($key);

        if (! $expected) {
            return response()->json(['error' => 'No valid OTP found or it has expired'], 400);
        }

        if ((string) $expected !== (string) $data['code']) {
            return response()->json(['error' => 'Invalid OTP code'], 400);
        }

        Cache::forget($key);

        $token = $user->createToken('agriAid-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
