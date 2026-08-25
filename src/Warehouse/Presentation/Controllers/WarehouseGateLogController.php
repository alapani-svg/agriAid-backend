<?php

namespace App\Warehouse\Presentation\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseGateLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Gate logistics manifest: incoming/outgoing vehicle movements manually logged
 * by warehouse managers at the warehouse gate. Real, persisted data.
 */
class WarehouseGateLogController
{
    public function index(Request $request, string $warehouseId): JsonResponse
    {
        if (Warehouse::find($warehouseId) === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $limit = min(200, max(1, (int) $request->query('limit', 20)));

        $query = WarehouseGateLog::where('warehouse_id', $warehouseId);

        if ($direction = $request->query('direction')) {
            $query->where('direction', $direction);
        }

        $logs = $query->orderByDesc('occurred_at')->limit($limit)->get();

        return response()->json([
            'data' => $logs->map(fn (WarehouseGateLog $l) => $this->toArray($l)),
        ]);
    }

    public function store(Request $request, string $warehouseId): JsonResponse
    {
        if (Warehouse::find($warehouseId) === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $data = $request->validate([
            'direction' => ['required', Rule::in(WarehouseGateLog::DIRECTIONS)],
            'vehicle_id' => ['required', 'string', 'max:60'],
            'commodity' => ['required', 'string', 'max:120'],
            'weight_kg' => ['required', 'numeric', 'gt:0'],
            'gate' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $log = WarehouseGateLog::create([
            'id' => (string) Str::uuid(),
            'warehouse_id' => $warehouseId,
            'direction' => $data['direction'],
            'vehicle_id' => $data['vehicle_id'],
            'commodity' => $data['commodity'],
            'weight_kg' => $data['weight_kg'],
            'gate' => $data['gate'] ?? null,
            'notes' => $data['notes'] ?? null,
            'recorded_by_user_id' => $request->user()?->id,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return response()->json($this->toArray($log), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WarehouseGateLog $log): array
    {
        return [
            'id' => $log->id,
            'warehouse_id' => $log->warehouse_id,
            'direction' => $log->direction,
            'vehicle_id' => $log->vehicle_id,
            'commodity' => $log->commodity,
            'weight_kg' => (float) $log->weight_kg,
            'gate' => $log->gate,
            'notes' => $log->notes,
            'recorded_by_user_id' => $log->recorded_by_user_id,
            'occurred_at' => $log->occurred_at?->format('Y-m-d H:i:s'),
            'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
