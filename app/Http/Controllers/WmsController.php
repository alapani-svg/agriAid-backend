<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseSensorReading;
use App\Models\WmsAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WmsController extends Controller
{
    /**
     * Biological threshold matrix for key Cameroonian crops.
     * Used to drive FEFO logic and critical alerts.
     */
    public const THRESHOLDS = [
        'tomato' => [
            'name' => 'Tomatoes',
            'optimal_temp_c' => [12, 15],
            'optimal_rh_pct' => [85, 95],
            'ethylene_sensitivity' => 'high',
            'initial_shelf_life_hours' => 168,
            'critical_shelf_life_hours' => 48,
            'warning_shelf_life_hours' => 72,
            'temp_penalty_per_hour' => 2.5,
            'rh_penalty_per_point_per_hour' => 0.5,
        ],
        'leafy_greens' => [
            'name' => 'Leafy Greens (Njama-Njama)',
            'optimal_temp_c' => [0, 2],
            'optimal_rh_pct' => [90, 98],
            'ethylene_sensitivity' => 'very_high',
            'initial_shelf_life_hours' => 96,
            'critical_shelf_life_hours' => 24,
            'warning_shelf_life_hours' => 48,
            'temp_penalty_per_hour' => 4.0,
            'rh_penalty_per_point_per_hour' => 1.0,
        ],
        'plantain' => [
            'name' => 'Plantains / Bananas',
            'optimal_temp_c' => [13, 14],
            'optimal_rh_pct' => [80, 90],
            'ethylene_sensitivity' => 'producer',
            'initial_shelf_life_hours' => 336,
            'critical_shelf_life_hours' => 72,
            'warning_shelf_life_hours' => 120,
            'temp_penalty_per_hour' => 1.0,
            'rh_penalty_per_point_per_hour' => 0.3,
        ],
        'onion' => [
            'name' => 'Onions & Garlic',
            'optimal_temp_c' => [0, 0],
            'optimal_rh_pct' => [65, 70],
            'ethylene_sensitivity' => 'low',
            'initial_shelf_life_hours' => 168,
            'critical_shelf_life_hours' => 24,
            'warning_shelf_life_hours' => 72,
            'temp_penalty_per_hour' => 0.6,
            'rh_penalty_per_point_per_hour' => 0.4,
        ],
        'maize' => [
            'name' => 'Maize',
            'optimal_temp_c' => [10, 15],
            'optimal_rh_pct' => [60, 70],
            'ethylene_sensitivity' => 'low',
            'initial_shelf_life_hours' => 720,
            'critical_shelf_life_hours' => 168,
            'warning_shelf_life_hours' => 336,
            'temp_penalty_per_hour' => 0.3,
            'rh_penalty_per_point_per_hour' => 0.2,
        ],
        'rice' => [
            'name' => 'Rice',
            'optimal_temp_c' => [12, 15],
            'optimal_rh_pct' => [60, 70],
            'ethylene_sensitivity' => 'low',
            'initial_shelf_life_hours' => 1440,
            'critical_shelf_life_hours' => 336,
            'warning_shelf_life_hours' => 672,
            'temp_penalty_per_hour' => 0.2,
            'rh_penalty_per_point_per_hour' => 0.15,
        ],
        'cassava' => [
            'name' => 'Cassava',
            'optimal_temp_c' => [0, 5],
            'optimal_rh_pct' => [85, 95],
            'ethylene_sensitivity' => 'high',
            'initial_shelf_life_hours' => 48,
            'critical_shelf_life_hours' => 12,
            'warning_shelf_life_hours' => 24,
            'temp_penalty_per_hour' => 5.0,
            'rh_penalty_per_point_per_hour' => 1.5,
        ],
        'beans' => [
            'name' => 'Beans',
            'optimal_temp_c' => [10, 15],
            'optimal_rh_pct' => [60, 70],
            'ethylene_sensitivity' => 'low',
            'initial_shelf_life_hours' => 720,
            'critical_shelf_life_hours' => 168,
            'warning_shelf_life_hours' => 336,
            'temp_penalty_per_hour' => 0.4,
            'rh_penalty_per_point_per_hour' => 0.3,
        ],
    ];

    public function thresholds(): JsonResponse
    {
        return response()->json(self::THRESHOLDS);
    }

    /**
     * Overview filtered by warehouse_id, farmer_id, or manager_user_id.
     * Admin can see all; farmer sees own; warehouse manager sees assigned.
     */
    public function overview(Request $request): JsonResponse
    {
        $query = Warehouse::query();

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('id', $warehouseId);
        }

        if ($farmerId = $request->query('farmer_id')) {
            $query->where('farmer_id', $farmerId);
        }

        if ($managerId = $request->query('manager_user_id')) {
            $query->where('manager_user_id', $managerId);
        }

        $warehouses = $query->get(['id', 'name', 'region', 'farmer_id']);
        $latestReadings = $this->latestReadingsByWarehouse();

        $result = $warehouses->map(function (Warehouse $warehouse) use ($latestReadings) {
            $stocks = $this->stocksForWarehouse($warehouse->id);
            $analysis = $stocks->map(fn (Stock $stock) => $this->analyseStock($stock, $latestReadings[$warehouse->id] ?? null));

            // Note: syncAlerts is now called only from the alerts endpoint or scheduled task
            // to avoid heavy write operations on every overview request

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'warehouse_region' => $warehouse->region,
                'farmer_id' => $warehouse->farmer_id,
                'latest_sensor' => $latestReadings[$warehouse->id] ?? null,
                'stock_summary' => [
                    'total_lots' => $analysis->count(),
                    'good' => $analysis->where('status', 'good')->count(),
                    'warning' => $analysis->where('status', 'warning')->count(),
                    'critical' => $analysis->where('status', 'critical')->count(),
                    'expired' => $analysis->where('status', 'expired')->count(),
                    'critical_stock_weight_kg' => round($analysis->where('status', 'critical')->sum('quantity_kg'), 2),
                    'total_stock_weight_kg' => round($analysis->sum('quantity_kg'), 2),
                ],
                'alerts' => $analysis->where('alert_level', '>=', 1)->values(),
            ];
        });

        return response()->json($result);
    }

    public function pickList(Request $request): JsonResponse
    {
        $query = Stock::query()->where('status', 'in_stock');

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($farmerId = $request->query('farmer_id')) {
            $query->whereHas('warehouse', fn ($q) => $q->where('farmer_id', $farmerId));
        }

        if ($cropFilter = $request->query('crop_type')) {
            $query->where('crop_type', $cropFilter);
        }

        $stocks = $query->get();
        $latestReadings = $this->latestReadingsByWarehouse();

        $pickList = $stocks
            ->map(fn (Stock $stock) => $this->analyseStock($stock, $latestReadings[$stock->warehouse_id] ?? null))
            ->where('status', '!=', 'expired')
            ->sortBy('fefo_priority')
            ->values();

        return response()->json([
            'pick_list' => $pickList,
            'fefo_rule' => 'First Expired, First Out: lots are ordered by the lowest remaining shelf life and highest deterioration risk.',
        ]);
    }

    public function alerts(Request $request): JsonResponse
    {
        // Sync alerts from live stock analysis before returning
        $this->syncAlertsFromLive($request);

        $query = WmsAlert::query()->where('acknowledged', false);

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($farmerId = $request->query('farmer_id')) {
            $query->where('farmer_id', $farmerId);
        }

        $alerts = $query->orderBy('alert_level', 'desc')
            ->orderBy('shelf_life_hours', 'asc')
            ->get()
            ->map(fn (WmsAlert $alert) => [
                'id' => $alert->id,
                'stock_id' => $alert->stock_id,
                'lot_id' => $alert->lot_id,
                'warehouse_id' => $alert->warehouse_id,
                'farmer_id' => $alert->farmer_id,
                'crop_type' => $alert->crop_type,
                'crop_display_name' => $alert->crop_display_name ?? $alert->crop_type,
                'quantity_kg' => (float) $alert->quantity_kg,
                'quality_grade' => $alert->quality_grade,
                'shelf_life_hours' => $alert->shelf_life_hours,
                'status' => $alert->status,
                'alert_level' => $alert->alert_level,
                'recommended_action' => $alert->recommended_action,
                'alert_reasons' => $alert->alert_reasons ?? [],
                'current_temperature_c' => $alert->current_temperature_c,
                'current_humidity_pct' => $alert->current_humidity_pct,
                'acknowledged' => $alert->acknowledged,
                'created_at' => $alert->created_at?->toIso8601String(),
            ]);

        return response()->json($alerts);
    }

    public function acknowledgeAlert(Request $request, string $alertId): JsonResponse
    {
        $alert = WmsAlert::find($alertId);
        if (!$alert) {
            return response()->json(['message' => 'Alert not found'], 404);
        }

        $alert->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Alert acknowledged']);
    }

    public function acknowledgeAll(Request $request): JsonResponse
    {
        $query = WmsAlert::query()->where('acknowledged', false);

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($farmerId = $request->query('farmer_id')) {
            $query->where('farmer_id', $farmerId);
        }

        $count = $query->update([
            'acknowledged' => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => $request->user()->id,
        ]);

        return response()->json(['message' => "{$count} alerts acknowledged"]);
    }

    /**
     * Sync computed alerts to the wms_alerts table for persistence.
     * Called only from the alerts endpoint (not overview) to reduce write load.
     */
    private function syncAlertsFromLive(Request $request): void
    {
        $query = Warehouse::query();

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('id', $warehouseId);
        }

        if ($farmerId = $request->query('farmer_id')) {
            $query->where('farmer_id', $farmerId);
        }

        if ($managerId = $request->query('manager_user_id')) {
            $query->where('manager_user_id', $managerId);
        }

        $warehouses = $query->get(['id', 'name', 'region', 'farmer_id']);
        $latestReadings = $this->latestReadingsByWarehouse();

        foreach ($warehouses as $warehouse) {
            $stocks = $this->stocksForWarehouse($warehouse->id);
            $analysis = $stocks->map(fn (Stock $stock) => $this->analyseStock($stock, $latestReadings[$warehouse->id] ?? null));
            $this->syncAlerts($warehouse, $analysis);
        }
    }

    /**
     * Sync computed alerts to the wms_alerts table for persistence.
     */
    private function syncAlerts(Warehouse $warehouse, $analysis): void
    {
        $farmerId = $warehouse->farmer_id;

        foreach ($analysis as $item) {
            if ($item['alert_level'] >= 1) {
                WmsAlert::updateOrCreate(
                    [
                        'stock_id' => $item['stock_id'],
                    ],
                    [
                        'warehouse_id' => $warehouse->id,
                        'farmer_id' => $farmerId,
                        'crop_type' => $item['crop_type'],
                        'crop_display_name' => $item['crop_display_name'] ?? $item['crop_type'],
                        'lot_id' => $item['lot_id'],
                        'quantity_kg' => $item['quantity_kg'],
                        'quality_grade' => $item['quality_grade'],
                        'shelf_life_hours' => $item['shelf_life_hours'],
                        'status' => $item['status'],
                        'alert_level' => $item['alert_level'],
                        'recommended_action' => $item['recommended_action'],
                        'alert_reasons' => $item['alert_reasons'],
                        'current_temperature_c' => $item['current_temperature_c'],
                        'current_humidity_pct' => $item['current_humidity_pct'],
                    ]
                );
            } else {
                // Remove alert if stock is now good
                WmsAlert::where('stock_id', $item['stock_id'])->delete();
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>|null>
     */
    private function latestReadingsByWarehouse(): array
    {
        // Single query: get the latest reading per warehouse using a subquery
        $latest = WarehouseSensorReading::query()
            ->select('warehouse_id', 'temperature_celsius', 'moisture_pct', 'recorded_at')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')
                    ->from('warehouse_sensor_readings')
                    ->groupBy('warehouse_id');
            })
            ->get();

        $readings = [];
        foreach ($latest as $reading) {
            $readings[$reading->warehouse_id] = [
                'temperature_celsius' => $reading->temperature_celsius !== null ? (float) $reading->temperature_celsius : null,
                'moisture_pct' => $reading->moisture_pct !== null ? (float) $reading->moisture_pct : null,
                'recorded_at' => $reading->recorded_at?->toDateTimeString(),
            ];
        }

        return $readings;
    }

    /**
     * @return \Illuminate\Support\Collection<int, Stock>
     */
    private function stocksForWarehouse(string $warehouseId): \Illuminate\Support\Collection
    {
        return Stock::where('warehouse_id', $warehouseId)
            ->where('status', 'in_stock')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function analyseStock(Stock $stock, ?array $reading): array
    {
        $cropKey = $this->normalizeCrop($stock->crop_type);
        $threshold = self::THRESHOLDS[$cropKey] ?? self::THRESHOLDS['tomato'];

        $entryAt = $stock->entry_date ? $stock->entry_date->startOfDay() : now()->subDay();
        $hoursInStorage = max(0, (int) $entryAt->diffInHours(now()));

        $temperature = $reading['temperature_celsius'] ?? null;
        $humidity = $reading['moisture_pct'] ?? null;

        $shelfLife = $threshold['initial_shelf_life_hours'] - $hoursInStorage;

        if ($temperature !== null) {
            [$tMin, $tMax] = $threshold['optimal_temp_c'];
            if ($temperature < $tMin) {
                $shelfLife -= abs($temperature - $tMin) * $threshold['temp_penalty_per_hour'] * $hoursInStorage;
            } elseif ($temperature > $tMax) {
                $shelfLife -= ($temperature - $tMax) * $threshold['temp_penalty_per_hour'] * $hoursInStorage;
            }
        }

        if ($humidity !== null) {
            [$hMin, $hMax] = $threshold['optimal_rh_pct'];
            if ($humidity < $hMin) {
                $shelfLife -= abs($humidity - $hMin) * $threshold['rh_penalty_per_point_per_hour'] * $hoursInStorage;
            } elseif ($humidity > $hMax) {
                $shelfLife -= ($humidity - $hMax) * $threshold['rh_penalty_per_point_per_hour'] * $hoursInStorage;
            }
        }

        $shelfLife = max(0, (int) round($shelfLife));

        $qualityPenalty = $this->qualityPenalty($stock->quality_grade);
        $fefoPriority = $shelfLife + $qualityPenalty;

        if ($shelfLife <= 0) {
            $status = 'expired';
            $alertLevel = 3;
            $action = 'liquidate';
        } elseif ($shelfLife <= $threshold['critical_shelf_life_hours']) {
            $status = 'critical';
            $alertLevel = 2;
            $action = 'discount';
        } elseif ($shelfLife <= $threshold['warning_shelf_life_hours']) {
            $status = 'warning';
            $alertLevel = 1;
            $action = 'watch';
        } else {
            $status = 'good';
            $alertLevel = 0;
            $action = 'hold';
        }

        $reasons = [];
        if ($shelfLife <= 0) {
            $reasons[] = 'Batch has exceeded its commercial shelf life.';
        } elseif ($shelfLife <= $threshold['critical_shelf_life_hours']) {
            $reasons[] = 'Shelf-life window is critical.';
        } elseif ($shelfLife <= $threshold['warning_shelf_life_hours']) {
            $reasons[] = 'Shelf-life window is approaching critical.';
        }

        if ($temperature !== null) {
            [$tMin, $tMax] = $threshold['optimal_temp_c'];
            if ($temperature > $tMax + 5) {
                $reasons[] = "Temperature {$temperature}°C far exceeds optimal range ({$tMin}-{$tMax}°C).";
            } elseif ($temperature < $tMin - 2) {
                $reasons[] = "Temperature {$temperature}°C below optimal range ({$tMin}-{$tMax}°C).";
            } elseif ($temperature > $tMax) {
                $reasons[] = "Temperature {$temperature}°C slightly above optimal range ({$tMin}-{$tMax}°C).";
            }
        }

        if ($humidity !== null) {
            [$hMin, $hMax] = $threshold['optimal_rh_pct'];
            if ($humidity > $hMax + 10) {
                $reasons[] = "Humidity {$humidity}% far exceeds optimal range ({$hMin}-{$hMax}%).";
            } elseif ($humidity < $hMin - 5) {
                $reasons[] = "Humidity {$humidity}% below optimal range ({$hMin}-{$hMax}%).";
            }
        }

        return [
            'stock_id' => $stock->id,
            'lot_id' => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'crop_type' => $stock->crop_type,
            'crop_display_name' => $threshold['name'],
            'variety' => $stock->variety ?? null,
            'quantity_kg' => (float) $stock->quantity_kg,
            'quality_grade' => $stock->quality_grade,
            'entry_date' => $stock->entry_date?->toDateString(),
            'hours_in_storage' => (int) $hoursInStorage,
            'current_temperature_c' => $temperature,
            'current_humidity_pct' => $humidity,
            'optimal_temp_c' => $threshold['optimal_temp_c'],
            'optimal_rh_pct' => $threshold['optimal_rh_pct'],
            'shelf_life_hours' => $shelfLife,
            'status' => $status,
            'alert_level' => $alertLevel,
            'recommended_action' => $action,
            'fefo_priority' => (float) $fefoPriority,
            'alert_reasons' => $reasons,
        ];
    }

    private function normalizeCrop(?string $crop): string
    {
        if (! $crop) {
            return 'tomato';
        }

        $lower = strtolower(trim($crop));

        return match (true) {
            str_contains($lower, 'tomato') => 'tomato',
            str_contains($lower, 'leaf') || str_contains($lower, 'njama') || str_contains($lower, 'green') => 'leafy_greens',
            str_contains($lower, 'plantain') || str_contains($lower, 'banana') => 'plantain',
            str_contains($lower, 'onion') || str_contains($lower, 'garlic') => 'onion',
            str_contains($lower, 'maize') || str_contains($lower, 'corn') => 'maize',
            str_contains($lower, 'rice') => 'rice',
            str_contains($lower, 'cassava') => 'cassava',
            str_contains($lower, 'bean') => 'beans',
            default => 'tomato',
        };
    }

    private function qualityPenalty(?string $grade): int
    {
        return match ($grade) {
            'A', 'Grade A' => 0,
            'B', 'Grade B' => 12,
            'C', 'Grade C' => 24,
            default => 6,
        };
    }
}
