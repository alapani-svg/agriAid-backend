<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const CODED_ROLES = ['lender', 'warehouse', 'government'];

    public function __construct(
        private readonly OtpService $otpService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::ROLES)],
            'region' => ['required', 'string', 'max:60'],
            'organization' => ['nullable', 'string', 'max:255'],
            'access_code' => ['nullable', 'string', 'max:64'],
        ]);

        $this->assertAccessCode($data['role'], $data['access_code'] ?? null);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'notification_preference' => 'email',
            'role' => $data['role'],
            'region' => $data['region'],
            'organization' => $data['organization'] ?? null,
            'status' => 'pending',
        ]);

        $this->otpService->issueAndSend($user, 'login');

        return response()->json([
            'message' => 'Registered. A 6-digit verification code was sent to your email.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status === 'suspended') {
            throw ValidationException::withMessages([
                'email' => ['This account has been suspended. Contact support.'],
            ]);
        }

        $this->otpService->issueAndSend($user, 'login');

        return response()->json([
            'message' => 'A 6-digit verification code was sent to your email.',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Request a password-reset OTP. Always returns the same message
     * so callers cannot probe which emails exist.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user && $user->status !== 'suspended') {
            $this->otpService->issueAndSend($user, 'password_reset');
        }

        return response()->json([
            'message' => 'If an account exists for that email, a 6-digit reset code was sent.',
            'email' => $data['email'],
        ]);
    }

    /**
     * Verify reset OTP and set a new password.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['We could not reset the password for this email.'],
            ]);
        }

        if ($user->status === 'suspended') {
            throw ValidationException::withMessages([
                'email' => ['This account has been suspended. Contact support.'],
            ]);
        }

        if (! $this->otpService->verify($user, $data['code'], 'password_reset')) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired reset code.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        // Invalidate existing API tokens after password change
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password updated. You can sign in with your new password.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    private function assertAccessCode(string $role, ?string $code): void
    {
        if (! in_array($role, self::CODED_ROLES, true)) {
            return;
        }

        $expected = match ($role) {
            'lender' => env('ACCESS_CODE_LENDER', 'LEND-2026'),
            'warehouse' => env('ACCESS_CODE_WAREHOUSE', 'WH-2026'),
            'government' => env('ACCESS_CODE_GOV', 'GOV-2026'),
            default => null,
        };

        if (! $expected || ! $code || ! hash_equals(strtoupper($expected), strtoupper(trim($code)))) {
            throw ValidationException::withMessages([
                'access_code' => [
                    'A valid access code is required for this role. Ask your organisation or agriAid admin.',
                ],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'region' => $user->region,
            'organization' => $user->organization,
            'status' => $user->status,
        ];
    }
}
