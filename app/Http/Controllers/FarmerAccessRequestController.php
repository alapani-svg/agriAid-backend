<?php

namespace App\Http\Controllers;

use App\Models\Farmer;
use App\Models\FarmerAccessRequest;
use App\Models\Notification;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FarmerAccessRequestController extends Controller
{
    /**
     * Lender: list their own access requests.
     * Farmer: list access requests targeting their profile.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'lender') {
            $requests = FarmerAccessRequest::where('lender_id', $user->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => $this->payload($r));
        } elseif ($user->role === 'farmer') {
            $farmer = Farmer::where('user_id', $user->id)->first();
            if (!$farmer) {
                return response()->json(['data' => []]);
            }
            $requests = FarmerAccessRequest::where('farmer_id', $farmer->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => $this->payload($r));
        } else {
            // Admin can see all
            $requests = FarmerAccessRequest::orderByDesc('created_at')
                ->get()
                ->map(fn ($r) => $this->payload($r));
        }

        return response()->json(['data' => $requests]);
    }

    /**
     * Lender: create a new access request for a farmer's profile.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'lender') {
            return response()->json(['error' => 'Only lenders can request access'], 403);
        }

        $data = $request->validate([
            'farmer_id' => 'required|string|exists:farmers,id',
            'reason' => 'required|string|max:2000',
            'lender_institution' => 'nullable|string|max:255',
        ]);

        $farmer = Farmer::find($data['farmer_id']);
        if (!$farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        // Check if there's already a pending or active request from this lender
        $existing = FarmerAccessRequest::where('farmer_id', $farmer->id)
            ->where('lender_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existing) {
            if ($existing->status === 'approved' && $existing->isActive()) {
                return response()->json([
                    'error' => 'You already have active access to this farmer\'s profile',
                    'access_request' => $this->payload($existing),
                ], 409);
            }
            if ($existing->status === 'pending') {
                return response()->json([
                    'error' => 'You already have a pending request for this farmer',
                    'access_request' => $this->payload($existing),
                ], 409);
            }
        }

        $accessRequest = FarmerAccessRequest::create([
            'farmer_id' => $farmer->id,
            'lender_id' => $user->id,
            'lender_name' => $user->name,
            'lender_email' => $user->email,
            'lender_institution' => $data['lender_institution'] ?? null,
            'reason' => $data['reason'],
            'status' => 'pending',
        ]);

        // Notify the farmer
        $farmerUser = User::find($farmer->user_id);
        if ($farmerUser) {
            Notification::create([
                'id' => (string) Str::uuid(),
                'user_id' => $farmerUser->id,
                'type' => 'access.request',
                'title' => 'Profile access request',
                'message' => "{$user->name} has requested temporary access to your farm profile. Review and approve or deny.",
                'deep_link' => '/dashboard/farmer',
                'idempotency_key' => 'access-request-' . $accessRequest->id,
                'status' => 'sent',
                'delivered_at' => now(),
            ]);
        }

        AuditLogger::log(
            action: 'access.requested',
            category: 'farmer',
            metadata: [
                'access_request_id' => $accessRequest->id,
                'farmer_id' => $farmer->id,
                'farmer_name' => $farmer->farm_name,
                'reason' => $data['reason'],
            ],
            auditableType: FarmerAccessRequest::class,
            auditableId: (int) $accessRequest->id,
        );

        return response()->json($this->payload($accessRequest), 201);
    }

    /**
     * Show a single access request.
     */
    public function show(string $id): JsonResponse
    {
        $accessRequest = FarmerAccessRequest::find($id);
        if (!$accessRequest) {
            return response()->json(['error' => 'Access request not found'], 404);
        }
        return response()->json($this->payload($accessRequest));
    }

    /**
     * Farmer: approve an access request, granting temporary access.
     * The farmer chooses a duration between 24 and 72 hours.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $accessRequest = FarmerAccessRequest::find($id);

        if (!$accessRequest) {
            return response()->json(['error' => 'Access request not found'], 404);
        }

        // Verify the farmer owns this profile
        $farmer = Farmer::where('user_id', $user->id)->first();
        if (!$farmer || $farmer->id !== $accessRequest->farmer_id) {
            return response()->json(['error' => 'You can only approve access to your own profile'], 403);
        }

        if ($accessRequest->status !== 'pending') {
            return response()->json(['error' => 'This request has already been processed'], 422);
        }

        $data = $request->validate([
            'duration_hours' => 'required|integer|min:24|max:72',
            'farmer_note' => 'nullable|string|max:1000',
        ]);

        $accessRequest->status = 'approved';
        $accessRequest->approved_at = now();
        $accessRequest->approved_by = $user->id;
        $accessRequest->expires_at = now()->addHours((int) $data['duration_hours']);
        $accessRequest->farmer_note = $data['farmer_note'] ?? null;
        $accessRequest->save();

        // Notify the lender
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $accessRequest->lender_id,
            'type' => 'access.approved',
            'title' => 'Profile access approved',
            'message' => "Your request to access {$farmer->farm_name}'s profile has been approved. Access expires in {$data['duration_hours']} hours.",
            'deep_link' => '/dashboard/lender',
            'idempotency_key' => 'access-approved-' . $accessRequest->id,
            'status' => 'sent',
            'delivered_at' => now(),
        ]);

        AuditLogger::log(
            action: 'access.approved',
            category: 'farmer',
            metadata: [
                'access_request_id' => $accessRequest->id,
                'farmer_id' => $farmer->id,
                'lender_id' => $accessRequest->lender_id,
                'duration_hours' => $data['duration_hours'],
                'expires_at' => $accessRequest->expires_at->toIso8601String(),
            ],
            auditableType: FarmerAccessRequest::class,
            auditableId: (int) $accessRequest->id,
        );

        return response()->json($this->payload($accessRequest));
    }

    /**
     * Farmer: deny an access request.
     */
    public function deny(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $accessRequest = FarmerAccessRequest::find($id);

        if (!$accessRequest) {
            return response()->json(['error' => 'Access request not found'], 404);
        }

        $farmer = Farmer::where('user_id', $user->id)->first();
        if (!$farmer || $farmer->id !== $accessRequest->farmer_id) {
            return response()->json(['error' => 'You can only deny access to your own profile'], 403);
        }

        if ($accessRequest->status !== 'pending') {
            return response()->json(['error' => 'This request has already been processed'], 422);
        }

        $data = $request->validate([
            'farmer_note' => 'nullable|string|max:1000',
        ]);

        $accessRequest->status = 'denied';
        $accessRequest->farmer_note = $data['farmer_note'] ?? null;
        $accessRequest->save();

        // Notify the lender
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $accessRequest->lender_id,
            'type' => 'access.denied',
            'title' => 'Profile access denied',
            'message' => "Your request to access {$farmer->farm_name}'s profile has been denied.",
            'deep_link' => '/dashboard/lender',
            'idempotency_key' => 'access-denied-' . $accessRequest->id,
            'status' => 'sent',
            'delivered_at' => now(),
        ]);

        AuditLogger::log(
            action: 'access.denied',
            category: 'farmer',
            metadata: [
                'access_request_id' => $accessRequest->id,
                'farmer_id' => $farmer->id,
                'lender_id' => $accessRequest->lender_id,
            ],
            auditableType: FarmerAccessRequest::class,
            auditableId: (int) $accessRequest->id,
        );

        return response()->json($this->payload($accessRequest));
    }

    /**
     * Farmer: revoke a previously approved access request early.
     */
    public function revoke(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $accessRequest = FarmerAccessRequest::find($id);

        if (!$accessRequest) {
            return response()->json(['error' => 'Access request not found'], 404);
        }

        $farmer = Farmer::where('user_id', $user->id)->first();
        if (!$farmer || $farmer->id !== $accessRequest->farmer_id) {
            return response()->json(['error' => 'You can only revoke access to your own profile'], 403);
        }

        if ($accessRequest->status !== 'approved') {
            return response()->json(['error' => 'Only approved requests can be revoked'], 422);
        }

        $accessRequest->status = 'revoked';
        $accessRequest->save();

        // Notify the lender
        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $accessRequest->lender_id,
            'type' => 'access.revoked',
            'title' => 'Profile access revoked',
            'message' => "Your access to {$farmer->farm_name}'s profile has been revoked by the farmer.",
            'deep_link' => '/dashboard/lender',
            'idempotency_key' => 'access-revoked-' . $accessRequest->id,
            'status' => 'sent',
            'delivered_at' => now(),
        ]);

        AuditLogger::log(
            action: 'access.revoked',
            category: 'farmer',
            metadata: [
                'access_request_id' => $accessRequest->id,
                'farmer_id' => $farmer->id,
                'lender_id' => $accessRequest->lender_id,
            ],
            auditableType: FarmerAccessRequest::class,
            auditableId: (int) $accessRequest->id,
        );

        return response()->json($this->payload($accessRequest));
    }

    /**
     * Lender: view a farmer's full profile if they have active access.
     * Returns the farmer profile + credibility score.
     */
    public function viewFarmerProfile(Request $request, string $farmerId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'lender') {
            return response()->json(['error' => 'Only lenders can use this endpoint'], 403);
        }

        $farmer = Farmer::with('user:id,name,email,role,region,phone')->find($farmerId);
        if (!$farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        // Check for active access
        $access = FarmerAccessRequest::where('farmer_id', $farmerId)
            ->where('lender_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->first(fn ($r) => $r->isActive());

        if (!$access) {
            return response()->json([
                'error' => 'You do not have active access to this farmer\'s profile. Request access first.',
                'has_access' => false,
            ], 403);
        }

        // Return the farmer profile + access info
        return response()->json([
            'has_access' => true,
            'access_request' => $this->payload($access),
            'farmer' => [
                'id' => $farmer->id,
                'user_id' => $farmer->user_id,
                'farm_name' => $farmer->farm_name,
                'farm_size' => (float) $farmer->farm_size,
                'crops' => $farmer->crops ?? [],
                'region' => $farmer->region,
                'village' => $farmer->village,
                'phone' => $farmer->phone,
                'address' => $farmer->address,
                'cooperative_name' => $farmer->cooperative_name,
                'cooperative_id' => $farmer->cooperative_id,
                'status' => $farmer->status,
                'verified' => (bool) $farmer->verified,
                'created_at' => $farmer->created_at?->toIso8601String(),
                'user' => $farmer->user ? [
                    'name' => $farmer->user->name,
                    'email' => $farmer->user->email,
                    'region' => $farmer->user->region,
                    'phone' => $farmer->user->phone,
                ] : null,
            ],
        ]);
    }

    /**
     * Check if the lender has active access to a specific farmer.
     */
    public function checkAccess(Request $request, string $farmerId): JsonResponse
    {
        $user = $request->user();

        $access = FarmerAccessRequest::where('farmer_id', $farmerId)
            ->where('lender_id', $user->id)
            ->where('status', 'approved')
            ->get()
            ->first(fn ($r) => $r->isActive());

        $pending = FarmerAccessRequest::where('farmer_id', $farmerId)
            ->where('lender_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        return response()->json([
            'has_access' => $access !== null,
            'is_pending' => $pending,
            'access' => $access ? $this->payload($access) : null,
        ]);
    }

    /**
     * Build the API payload for an access request.
     */
    private function payload(FarmerAccessRequest $r): array
    {
        $farmer = Farmer::find($r->farmer_id);
        return [
            'id' => (string) $r->id,
            'farmer_id' => $r->farmer_id,
            'farmer_name' => $farmer?->farm_name ?? 'Unknown',
            'farmer_region' => $farmer?->region,
            'farmer_village' => $farmer?->village,
            'lender_id' => (string) $r->lender_id,
            'lender_name' => $r->lender_name,
            'lender_email' => $r->lender_email,
            'lender_institution' => $r->lender_institution,
            'reason' => $r->reason,
            'status' => $r->status,
            'approved_at' => $r->approved_at?->toIso8601String(),
            'expires_at' => $r->expires_at?->toIso8601String(),
            'is_active' => $r->isActive(),
            'farmer_note' => $r->farmer_note,
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
        ];
    }
}
