<?php

namespace App\Http\Controllers\Admin;

use App\Credibility\Application\Services\CredibilityScoreService;
use App\Credibility\Domain\ValueObjects\CredibilityScore;
use App\Farmer\Application\Services\UpdateFarmerProfileService;
use App\Http\Controllers\Controller;
use App\Models\Farmer as EloquentFarmer;
use App\Models\Harvest;
use App\Models\Stock;
use App\Models\User;
use App\Models\WarehouseReceipt;
use App\Services\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdminFarmerController extends Controller
{
    public function __construct(
        private readonly UpdateFarmerProfileService $updateFarmerProfileService,
        private readonly CredibilityScoreService $credibilityScoreService,
    ) {}

    /**
     * List all farmers with pagination, search and filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = EloquentFarmer::query()->with('user:id,name,email,role,region');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('farm_name', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%")
                    ->orWhere('village', 'like', "%{$search}%")
                    ->orWhere('cooperative_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($region = $request->query('region')) {
            $query->where('region', $region);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($request->has('verified')) {
            $verified = filter_var($request->query('verified'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($verified !== null) {
                $query->where('verified', $verified);
            }
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));
        $farmers = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => collect($farmers->items())->map(fn (EloquentFarmer $f) => $this->payload($f))->values(),
            'meta' => [
                'current_page' => $farmers->currentPage(),
                'last_page' => $farmers->lastPage(),
                'per_page' => $farmers->perPage(),
                'total' => $farmers->total(),
            ],
        ]);
    }

    /**
     * Show a single farmer's full profile.
     */
    public function show(string $id): JsonResponse
    {
        $farmer = EloquentFarmer::with('user:id,name,email,role,region,phone')->find($id);

        if (! $farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        return response()->json($this->payload($farmer));
    }

    /**
     * Generate a detailed PDF report for a single farmer.
     */
    public function exportPdf(string $id): Response
    {
        $farmer = EloquentFarmer::with('user:id,name,email,role,region,phone')->find($id);

        if (! $farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        $harvests = Harvest::where('farmer_id', $id)
            ->orderByDesc('harvest_date')
            ->limit(50)
            ->get();

        $stocks = Stock::where('seller_id', $id)
            ->orderByDesc('entry_date')
            ->limit(50)
            ->get();

        $receipts = WarehouseReceipt::where('farmer_id', $id)
            ->orderByDesc('issue_date')
            ->limit(50)
            ->get();

        $credibility = null;
        try {
            $breakdown = $this->credibilityScoreService->getBreakdown($id);
            $score = CredibilityScore::fromValue($breakdown['total_score']);
            $categories = $breakdown['categories'];

            $credibility = [
                'score' => $score->getValue(),
                'tier' => $score->getTier()->toString(),
                'tier_label' => $score->getTier()->label(),
                'max_financing_term_years' => $score->getMaxFinancingTermYears(),
                'movement_consistency_pct' => round($categories['movement_consistency']['raw_pct'], 1),
                'verified_movements_pct' => round($categories['verified_movements']['raw_pct'], 1),
                'repayment_history_pct' => round($categories['repayment_history']['raw_pct'], 1),
                'platform_use_length_pct' => round($categories['platform_use_length']['raw_pct'], 1),
                'certified_stock_volume_pct' => round($categories['certified_stock_volume']['raw_pct'], 1),
            ];
        } catch (\Exception $e) {
            $credibility = null;
        }

        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('exports.admin-farmer-detail', [
            'farmer' => $farmer,
            'harvests' => $harvests,
            'stocks' => $stocks,
            'receipts' => $receipts,
            'credibility' => $credibility,
            'generatedAt' => $generatedAt,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $safeFarmName = preg_replace('/[^A-Za-z0-9\-_]+/', '-', (string) $farmer->farm_name);
        $safeFarmName = trim($safeFarmName, '-') ?: 'farmer';
        $fileName = "agriaid-farmer-{$safeFarmName}-" . now()->format('Y-m-d') . '.pdf';

        AuditLogger::log(
            action: 'farmer.exported_pdf',
            category: 'farmer',
            metadata: ['farmer_id' => $id, 'farm_name' => $farmer->farm_name],
            auditableType: EloquentFarmer::class,
            auditableId: 0,
        );

        return $pdf->download($fileName);
    }

    /**
     * Update a farmer's profile.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'farm_name' => ['sometimes', 'string', 'max:255'],
            'farm_size' => ['sometimes', 'numeric', 'min:0'],
            'crops' => ['sometimes', 'array', 'min:1'],
            'crops.*' => ['string'],
            'region' => ['sometimes', 'string', 'max:60'],
            'village' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'cooperative_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cooperative_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,inactive,suspended'],
        ]);

        try {
            $farmer = $this->updateFarmerProfileService->execute(
                farmerId: $id,
                farmName: $data['farm_name'] ?? null,
                farmSize: isset($data['farm_size']) ? (float) $data['farm_size'] : null,
                crops: $data['crops'] ?? null,
                region: $data['region'] ?? null,
                village: $data['village'] ?? null,
                phone: $data['phone'] ?? null,
                address: $data['address'] ?? null,
                cooperativeName: $data['cooperative_name'] ?? null,
                cooperativeId: $data['cooperative_id'] ?? null,
            );

            // Status changes are handled directly on the Eloquent model since the
            // update-profile service only covers profile fields.
            if (array_key_exists('status', $data)) {
                $eloquent = EloquentFarmer::find($id);
                if ($eloquent) {
                    $eloquent->status = $data['status'];
                    $eloquent->save();
                }
            }

            $fresh = EloquentFarmer::with('user:id,name,email,role,region')->find($id);

            AuditLogger::log(
                action: 'farmer.updated',
                category: 'farmer',
                metadata: ['farmer_id' => $id, 'fields' => array_keys($data)],
                auditableType: EloquentFarmer::class,
                auditableId: 0,
            );

            return response()->json($this->payload($fresh));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a farmer profile and its associated user account.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $farmer = EloquentFarmer::find($id);

        if (! $farmer) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        $userId = $farmer->user_id;
        $farmName = $farmer->farm_name;

        $farmer->delete();

        $user = User::find($userId);
        if ($user) {
            $user->tokens()->delete();
            $user->delete();
        }

        AuditLogger::log(
            action: 'farmer.deleted',
            category: 'security',
            metadata: ['farmer_id' => $id, 'farm_name' => $farmName, 'user_id' => $userId],
            auditableType: EloquentFarmer::class,
            auditableId: 0,
        );

        return response()->json(['message' => 'Farmer and associated user account deleted.']);
    }

    /**
     * Mark a farmer as verified.
     */
    public function verify(Request $request, string $id): JsonResponse
    {
        $eloquent = EloquentFarmer::find($id);

        if (! $eloquent) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        $eloquent->verified = true;
        $eloquent->verified_at = now();
        $eloquent->save();

        $fresh = EloquentFarmer::with('user:id,name,email,role,region')->find($id);

        AuditLogger::log(
            action: 'farmer.verified',
            category: 'farmer',
            metadata: ['farmer_id' => $id, 'farm_name' => $eloquent->farm_name],
            auditableType: EloquentFarmer::class,
            auditableId: 0,
        );

        return response()->json($this->payload($fresh));
    }

    /**
     * Remove the verified badge from a farmer.
     */
    public function unverify(Request $request, string $id): JsonResponse
    {
        $eloquent = EloquentFarmer::find($id);

        if (! $eloquent) {
            return response()->json(['error' => 'Farmer not found'], 404);
        }

        $eloquent->verified = false;
        $eloquent->verified_at = null;
        $eloquent->save();

        $fresh = EloquentFarmer::with('user:id,name,email,role,region')->find($id);

        AuditLogger::log(
            action: 'farmer.unverified',
            category: 'farmer',
            metadata: ['farmer_id' => $id, 'farm_name' => $eloquent->farm_name],
            auditableType: EloquentFarmer::class,
            auditableId: 0,
        );

        return response()->json($this->payload($fresh));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(EloquentFarmer $farmer): array
    {
        return [
            'id' => (string) $farmer->id,
            'user_id' => (string) $farmer->user_id,
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
            'verified_at' => $farmer->verified_at?->toIso8601String(),
            'created_at' => $farmer->created_at?->toIso8601String(),
            'updated_at' => $farmer->updated_at?->toIso8601String(),
            'user' => $farmer->relationLoaded('user') && $farmer->user ? [
                'id' => (string) $farmer->user->id,
                'name' => $farmer->user->name,
                'email' => $farmer->user->email,
                'role' => $farmer->user->role,
                'region' => $farmer->user->region,
            ] : null,
        ];
    }
}
