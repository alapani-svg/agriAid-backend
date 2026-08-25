<?php

namespace App\Warehouse\Presentation\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseSensorReading;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Manually-logged environmental telemetry (temperature / moisture) for a warehouse.
 * This is real, persisted data entered by warehouse managers — there is no
 * physical IoT sensor hardware behind it.
 */
class WarehouseSensorReadingController
{
    public function index(Request $request, string $warehouseId): JsonResponse
    {
        if (Warehouse::find($warehouseId) === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $limit = min(200, max(1, (int) $request->query('limit', 20)));

        $readings = WarehouseSensorReading::where('warehouse_id', $warehouseId)
            ->orderByDesc('recorded_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $readings->map(fn (WarehouseSensorReading $r) => $this->toArray($r)),
        ]);
    }

    public function store(Request $request, string $warehouseId): JsonResponse
    {
        if (Warehouse::find($warehouseId) === null) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $data = $request->validate([
            'temperature_celsius' => ['nullable', 'numeric', 'between:-50,80'],
            'moisture_pct' => ['nullable', 'numeric', 'between:0,100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        if (!array_key_exists('temperature_celsius', $data) && !array_key_exists('moisture_pct', $data)) {
            return response()->json([
                'error' => 'Provide at least one of temperature_celsius or moisture_pct',
            ], 422);
        }

        $reading = WarehouseSensorReading::create([
            'id' => (string) Str::uuid(),
            'warehouse_id' => $warehouseId,
            'temperature_celsius' => $data['temperature_celsius'] ?? null,
            'moisture_pct' => $data['moisture_pct'] ?? null,
            'recorded_by_user_id' => $request->user()?->id,
            'recorded_at' => $data['recorded_at'] ?? now(),
        ]);

        return response()->json($this->toArray($reading), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WarehouseSensorReading $reading): array
    {
        return [
            'id' => $reading->id,
            'warehouse_id' => $reading->warehouse_id,
            'temperature_celsius' => $reading->temperature_celsius !== null ? (float) $reading->temperature_celsius : null,
            'moisture_pct' => $reading->moisture_pct !== null ? (float) $reading->moisture_pct : null,
            'recorded_by_user_id' => $reading->recorded_by_user_id,
            'recorded_at' => $reading->recorded_at?->format('Y-m-d H:i:s'),
            'created_at' => $reading->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
