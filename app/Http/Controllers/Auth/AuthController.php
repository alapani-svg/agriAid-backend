<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\SendLoginOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Roles that require an organisation access code to join. */
    private const CODED_ROLES = ['lender', 'warehouse', 'government'];

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

        $this->issueAndSendOtp($user, 'login');

        return response()->json([
            'message' => 'Registered. OTP sent to your email.',
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

        $this->issueAndSendOtp($user, 'login');

        return response()->json([
            'message' => 'OTP sent to your email.',
            'user' => $this->userPayload($user),
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

    private function issueAndSendOtp(User $user, string $purpose): void
    {
        $code = (string) random_int(100000, 999999);
        $key = "otp:{$purpose}:{$user->id}";

        Cache::put($key, $code, now()->addMinutes(10));

        $user->notify(new SendLoginOtpNotification($code, $purpose));
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
