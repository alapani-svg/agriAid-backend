<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseSensorReading;
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
    ];

    public function thresholds(): JsonResponse
    {
        return response()->json(self::THRESHOLDS);
    }

    public function overview(): JsonResponse
    {
        $warehouses = Warehouse::all(['id', 'name', 'region']);
        $latestReadings = $this->latestReadingsByWarehouse();

        $result = $warehouses->map(function (Warehouse $warehouse) use ($latestReadings) {
            $stocks = $this->stocksForWarehouse($warehouse->id);
            $analysis = $stocks->map(fn (Stock $stock) => $this->analyseStock($stock, $latestReadings[$warehouse->id] ?? null));

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name,
                'warehouse_region' => $warehouse->region,
                'latest_sensor' => $latestReadings[$warehouse->id] ?? null,
                'stock_summary' => [
                    'total_lots' => $analysis->count(),
                    'good' => $analysis->where('status', 'good')->count(),
                    'warning' => $analysis->where('status', 'warning')->count(),
                    'critical' => $analysis->where('status', 'critical')->count(),
                    'critical_stock_weight_kg' => round($analysis->where('status', 'critical')->sum('quantity_kg'), 2),
                ],
                'alerts' => $analysis->where('alert_level', '>=', 1)->values(),
            ];
        });

        return response()->json($result);
    }

    public function pickList(Request $request): JsonResponse
    {
        $warehouseId = $request->query('warehouse_id');
        $cropFilter = $request->query('crop_type');

        $query = Stock::query();

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($cropFilter) {
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

    public function alerts(): JsonResponse
    {
        $stocks = Stock::all();
        $latestReadings = $this->latestReadingsByWarehouse();

        $alerts = $stocks
            ->map(fn (Stock $stock) => $this->analyseStock($stock, $latestReadings[$stock->warehouse_id] ?? null))
            ->where('alert_level', '>=', 1)
            ->sortBy('fefo_priority')
            ->values();

        return response()->json($alerts);
    }

    /**
     * @return array<string, array<string, mixed>|null>
     */
    private function latestReadingsByWarehouse(): array
    {
        $latest = WarehouseSensorReading::query()
            ->selectRaw('warehouse_id, MAX(recorded_at) as latest_at')
            ->groupBy('warehouse_id')
            ->get();

        $readings = [];
        foreach ($latest as $row) {
            $reading = WarehouseSensorReading::where('warehouse_id', $row->warehouse_id)
                ->where('recorded_at', $row->latest_at)
                ->first();

            if ($reading) {
                $readings[$row->warehouse_id] = [
                    'temperature_celsius' => $reading->temperature_celsius !== null ? (float) $reading->temperature_celsius : null,
                    'moisture_pct' => $reading->moisture_pct !== null ? (float) $reading->moisture_pct : null,
                    'recorded_at' => $reading->recorded_at?->toDateTimeString(),
                ];
            }
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
        }

        if ($temperature !== null) {
            [$tMin, $tMax] = $threshold['optimal_temp_c'];
            if ($temperature > $tMax + 5) {
                $reasons[] = "Temperature {$temperature}°C far exceeds optimal range ({$tMin}-{$tMax}°C).";
            } elseif ($temperature < $tMin - 2) {
                $reasons[] = "Temperature {$temperature}°C below optimal range ({$tMin}-{$tMax}°C).";
            }
        }

        return [
            'stock_id' => $stock->id,
            'lot_id' => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'crop_type' => $stock->crop_type,
            'variety' => $stock->variety,
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
