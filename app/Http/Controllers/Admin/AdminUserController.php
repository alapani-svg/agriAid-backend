<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    private const STATUSES = ['pending', 'active', 'suspended'];

    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $users = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => array_map(fn (User $u) => $this->payload($u), $users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($this->payload($user));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(User::ROLES)],
            'region' => ['required', 'string', 'max:60'],
            'organization' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'notification_preference' => 'email',
            'role' => $data['role'],
            'region' => $data['region'],
            'organization' => $data['organization'] ?? null,
            // Admin-created accounts are active immediately; no OTP verification needed.
            'status' => $data['status'] ?? 'active',
        ]);

        AuditLogger::log(
            action: 'user.created',
            category: 'user',
            metadata: ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role],
            auditableType: User::class,
            auditableId: (int) $user->id,
        );

        return response()->json($this->payload($user), 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'role' => ['sometimes', Rule::in(User::ROLES)],
            'region' => ['sometimes', 'string', 'max:60'],
            'organization' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(self::STATUSES)],
        ]);

        $actingUser = $request->user();
        $changingRoleOrStatus = array_key_exists('role', $data) || array_key_exists('status', $data);

        if ($changingRoleOrStatus && $actingUser && $actingUser->id === $user->id) {
            return response()->json([
                'error' => 'Admins cannot change their own role or status. Ask another admin to do it.',
            ], 422);
        }

        $user->fill($data);

        if (array_key_exists('status', $data) && $data['status'] === 'active' && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        if (array_key_exists('role', $data)) {
            AuditLogger::log(
                action: 'user.role_changed',
                category: 'security',
                metadata: ['user_id' => $user->id, 'new_role' => $user->role],
                auditableType: User::class,
                auditableId: (int) $user->id,
            );
        }

        if (array_key_exists('status', $data)) {
            AuditLogger::log(
                action: 'user.status_changed',
                category: 'user',
                metadata: ['user_id' => $user->id, 'new_status' => $user->status],
                auditableType: User::class,
                auditableId: (int) $user->id,
            );
        }

        return response()->json($this->payload($user));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($request->user() && $request->user()->id === $user->id) {
            return response()->json(['error' => 'Admins cannot delete their own account.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        AuditLogger::log(
            action: 'user.deleted',
            category: 'security',
            metadata: ['user_id' => $id, 'email' => $user->email],
            auditableType: User::class,
            auditableId: (int) $id,
        );

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
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
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }
}
