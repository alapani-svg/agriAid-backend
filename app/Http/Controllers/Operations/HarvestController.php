<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Farmer;
use App\Models\Harvest;
use App\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class HarvestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farmer = $this->farmerFor($request);

        $harvests = Harvest::query()
            ->where('farmer_id', $farmer->id)
            ->orderByDesc('harvested_on')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Harvest $h) => $this->payload($h));

        return response()->json(['harvests' => $harvests]);
    }

    public function store(Request $request): JsonResponse
    {
        $farmer = $this->farmerFor($request, createIfMissing: true);

        $data = $request->validate([
            'crop' => ['required', 'string', 'max:80'],
            'mass_kg' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'quality_pct' => ['nullable', 'integer', 'min:1', 'max:100'],
            'price_per_kg' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(Harvest::STATUSES)],
            'village' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:60'],
            'harvested_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $harvest = DB::transaction(function () use ($farmer, $data) {
            $harvest = Harvest::create([
                'farmer_id' => $farmer->id,
                'crop' => $data['crop'],
                'mass_kg' => $data['mass_kg'],
                'quality_pct' => $data['quality_pct'] ?? 80,
                'price_per_kg' => $data['price_per_kg'] ?? null,
                'status' => $data['status'] ?? 'recorded',
                'village' => $data['village'] ?? $farmer->village,
                'region' => $data['region'] ?? $farmer->region,
                'harvested_on' => $data['harvested_on'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Auto-update stock ledger for this crop
            $stock = Stock::query()->firstOrNew([
                'farmer_id' => $farmer->id,
                'crop' => $harvest->crop,
            ]);

            $stock->quantity_kg = (float) $stock->quantity_kg + (float) $harvest->mass_kg;
            $stock->unit = 'kg';
            $stock->location = $stock->location ?: ($harvest->village ?: $farmer->village);
            $stock->save();

            // Keep crop_types profile in sync
            $crops = $farmer->crop_types ?? [];
            if (! in_array($harvest->crop, $crops, true)) {
                $crops[] = $harvest->crop;
                $farmer->crop_types = $crops;
                $farmer->save();
            }

            return $harvest;
        });

        return response()->json([
            'message' => 'Harvest recorded. Stock updated.',
            'harvest' => $this->payload($harvest),
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $farmer = $this->farmerFor($request);
        $harvest = Harvest::query()
            ->where('farmer_id', $farmer->id)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['harvest' => $this->payload($harvest)]);
    }

    private function farmerFor(Request $request, bool $createIfMissing = false): Farmer
    {
        $user = $request->user();
        $farmer = Farmer::query()->where('user_id', $user->id)->first();

        if ($farmer) {
            return $farmer;
        }

        if (! $createIfMissing) {
            abort(404, 'Complete your farmer profile first.');
        }

        return Farmer::create([
            'user_id' => $user->id,
            'region' => $user->region,
            'cig_group' => $user->organization,
            'crop_types' => [],
            'farm_size_hectares' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Harvest $h): array
    {
        return [
            'id' => $h->id,
            'crop' => $h->crop,
            'mass_kg' => (float) $h->mass_kg,
            'quality_pct' => (int) $h->quality_pct,
            'price_per_kg' => $h->price_per_kg !== null ? (float) $h->price_per_kg : null,
            'estimated_value' => $h->estimatedValue(),
            'status' => $h->status,
            'village' => $h->village,
            'region' => $h->region,
            'harvested_on' => $h->harvested_on?->toDateString(),
            'notes' => $h->notes,
            'created_at' => $h->created_at?->toIso8601String(),
        ];
    }
}
