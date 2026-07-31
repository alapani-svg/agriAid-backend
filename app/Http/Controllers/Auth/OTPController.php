<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OTPController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
    ) {}

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

        $this->otpService->issueAndSend($user, $purpose);

        return response()->json([
            'message' => 'A new 6-digit code was sent to your email.',
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

        if ($user->status === 'suspended') {
            return response()->json(['error' => 'This account has been suspended.'], 403);
        }

        if (! $this->otpService->verify($user, $data['code'], $purpose)) {
            return response()->json(['error' => 'Invalid or expired OTP code'], 400);
        }

        if ($user->status === 'pending') {
            $user->status = 'active';
            $user->email_verified_at = now();
            $user->save();
        }

        $token = $user->createToken('agriAid-auth-token')->plainTextToken;

        return response()->json([
            'message' => 'OTP verified successfully',
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'region' => $user->region,
                'organization' => $user->organization,
                'status' => $user->status,
            ],
        ]);
    }
}
