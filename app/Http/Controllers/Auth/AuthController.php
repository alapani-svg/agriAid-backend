<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    private const CODED_ROLES = ['lender', 'warehouse', 'government', 'admin'];

    /** Session-only token lifetime when Remember Me is off. */
    private const TOKEN_HOURS_SHORT = 24;

    /** Token lifetime when Remember Me is on. */
    private const TOKEN_DAYS_REMEMBER = 30;

    public function __construct(
        private readonly OtpService $otpService,
        private readonly NotificationApplicationService $notificationService,
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
            'access_code' => ['nullable', 'string', 'max:128'],
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

        $this->notificationService->notify(
            user: $user,
            type: NotificationType::WELCOME,
            title: 'Welcome to agriAid',
            message: 'Complete your profile and record your first harvest to get started.',
            deepLink: '/settings',
            idempotencyKey: "welcome:{$user->id}",
        );

        return response()->json([
            'message' => 'Registered. A 6-digit verification code was sent to your email.',
            'requires_otp' => true,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $remember = (bool) ($data['remember'] ?? false);

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

        $alreadyVerified = $user->status === 'active'
            || $user->email_verified_at !== null;

        if ($alreadyVerified) {
            if ($user->status !== 'active') {
                $user->status = 'active';
                $user->save();
            }

            $access = $this->issueToken($user, $remember);

            return response()->json([
                'message' => 'Signed in successfully.',
                'requires_otp' => false,
                'token' => $access->plainTextToken,
                'remember' => $remember,
                'token_expires_at' => $access->accessToken->expires_at?->toIso8601String(),
                'user' => $this->userPayload($user),
            ]);
        }

        $this->otpService->issueAndSend($user, 'login');

        return response()->json([
            'message' => 'A 6-digit verification code was sent to your email.',
            'requires_otp' => true,
            'remember' => $remember,
            'user' => $this->userPayload($user),
        ]);
    }

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

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'notification_preference' => ['sometimes', Rule::in(['email', 'sms', 'both', 'none'])],
            'region' => ['sometimes', 'string', 'max:60'],
            'organization' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->fill($data);
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->userPayload($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->password = $data['password'];
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function sessions(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->id;

        $tokens = $request->user()->tokens()->orderByDesc('last_used_at')->get()->map(fn ($token) => [
            'id' => (string) $token->id,
            'name' => $token->name,
            'last_used_at' => $token->last_used_at?->toIso8601String(),
            'created_at' => $token->created_at?->toIso8601String(),
            'is_current' => (string) $token->id === (string) $currentId,
        ]);

        return response()->json(['sessions' => $tokens]);
    }

    public function revokeSession(Request $request, string $tokenId): JsonResponse
    {
        $deleted = $request->user()->tokens()->where('id', $tokenId)->delete();

        if (! $deleted) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        return response()->json(['message' => 'Session revoked.']);
    }

    public function revokeOtherSessions(Request $request): JsonResponse
    {
        $currentId = $request->user()->currentAccessToken()?->id;

        $request->user()->tokens()->where('id', '!=', $currentId)->delete();

        return response()->json(['message' => 'All other sessions were signed out.']);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Your account has been permanently deleted.']);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->avatar_path = $path;
        $user->save();

        return response()->json([
            'message' => 'Profile picture updated.',
            'user' => $this->userPayload($user),
        ]);
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return response()->json([
            'message' => 'Profile picture removed.',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Issue a Sanctum personal access token with optional Remember Me lifetime.
     */
    public static function issueToken(User $user, bool $remember = false): NewAccessToken
    {
        $name = $remember ? 'agriAid-auth-token-remember' : 'agriAid-auth-token';

        $expiresAt = $remember
            ? now()->addDays(self::TOKEN_DAYS_REMEMBER)
            : now()->addHours(self::TOKEN_HOURS_SHORT);

        return $user->createToken($name, ['*'], $expiresAt);
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
            'admin' => env('ACCESS_CODE_ADMIN', 'c3eb7dab-ed1e-4c77-92a3-d28e485ddaec'),
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
            'avatar_url' => $user->avatarUrl(),
            'notification_preference' => $user->notification_preference,
        ];
    }
}
