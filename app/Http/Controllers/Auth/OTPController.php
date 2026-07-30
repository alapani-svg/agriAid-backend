<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendLoginOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $expiresAt = now()->addMinutes(10);

        DB::table('otps')->where('user_id', $user->id)->where('purpose', $purpose)->delete();

        $payload = [
            'user_id' => $user->id,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => $expiresAt,
            'verified_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            $payload['id'] = (string) Str::uuid();
            DB::table('otps')->insert($payload);
        } catch (\Throwable $e) {
            unset($payload['id']);
            DB::table('otps')->insert($payload);
        }

        $user->notify(new SendLoginOtpNotification($code, $purpose));

        return response()->json([
            'message' => 'OTP generated and sent successfully',
            'expires_at' => $expiresAt->toIso8601String(),
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

        $otp = DB::table('otps')
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $otp) {
            return response()->json(['error' => 'No valid OTP found'], 400);
        }

        if ($otp->expires_at && now()->greaterThan($otp->expires_at)) {
            return response()->json(['error' => 'OTP has expired'], 400);
        }

        if ((string) $otp->code !== (string) $data['code']) {
            return response()->json(['error' => 'Invalid OTP code'], 400);
        }

        DB::table('otps')->where('id', $otp->id)->update([
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

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
