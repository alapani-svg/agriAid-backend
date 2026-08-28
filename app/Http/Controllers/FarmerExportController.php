<?php

namespace App\Http\Controllers;

use App\Models\FarmerProfile;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FarmerExportController extends Controller
{
    /**
     * Export farmer data as CSV (opens in Excel).
     */
    public function exportCsv(Request $request, string $farmerId): StreamedResponse
    {
        $farmer = $this->resolveFarmer($request, $farmerId);
        $data = $this->collectFarmerData($farmer);

        $fileName = "farmer-{$farmer->farm_name}-export-" . now()->format('Y-m-d') . ".csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Harvests section
            fputcsv($handle, ['=== HARVESTS ===']);
            fputcsv($handle, ['Crop', 'Quantity (kg)', 'Quality Grade', 'Harvest Date', 'Status', 'Notes']);
            foreach ($data['harvests'] as $h) {
                fputcsv($handle, [
                    $h['crop_type'],
                    $h['quantity_kg'],
                    $h['quality_grade'] ?? '',
                    $h['harvest_date'],
                    $h['status'],
                    $h['notes'] ?? '',
                ]);
            }
            fputcsv($handle, []);

            // Stocks section
            fputcsv($handle, ['=== STOCK IN WAREHOUSES ===']);
            fputcsv($handle, ['Crop', 'Quantity (kg)', 'Warehouse', 'Entry Date', 'Status', 'Utilization %']);
            foreach ($data['stocks'] as $s) {
                fputcsv($handle, [
                    $s['crop_type'],
                    $s['quantity_kg'],
                    $s['warehouse_name'],
                    $s['entry_date'],
                    $s['status'],
                    $s['utilization_percentage'],
                ]);
            }
            fputcsv($handle, []);

            // Warehouses section
            fputcsv($handle, ['=== WAREHOUSES ===']);
            fputcsv($handle, ['Name', 'Region', 'Village', 'Capacity Total (kg)', 'Capacity Used (kg)', 'Status']);
            foreach ($data['warehouses'] as $w) {
                fputcsv($handle, [
                    $w['name'],
                    $w['region'],
                    $w['village'] ?? '',
                    $w['capacity_total_kg'],
                    $w['capacity_used_kg'],
                    $w['status'],
                ]);
            }
            fputcsv($handle, []);

            // Receipts section
            fputcsv($handle, ['=== WAREHOUSE RECEIPTS ===']);
            fputcsv($handle, ['Receipt #', 'Crop', 'Quantity (kg)', 'Issue Date', 'Status']);
            foreach ($data['receipts'] as $r) {
                fputcsv($handle, [
                    $r['receipt_number'],
                    $r['crop_type'],
                    $r['quantity_kg'],
                    $r['issue_date'],
                    $r['status'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export farmer data as PDF.
     */
    public function exportPdf(Request $request, string $farmerId): Response
    {
        $farmer = $this->resolveFarmer($request, $farmerId);
        $data = $this->collectFarmerData($farmer);

        $pdf = Pdf::loadView('exports.farmer-report', [
            'farmer' => $farmer,
            'harvests' => $data['harvests'],
            'stocks' => $data['stocks'],
            'warehouses' => $data['warehouses'],
            'receipts' => $data['receipts'],
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $fileName = "farmer-{$farmer->farm_name}-report-" . now()->format('Y-m-d') . ".pdf";

        return $pdf->download($fileName);
    }

    /**
     * Resolve the farmer profile, ensuring the requesting user has access.
     */
    private function resolveFarmer(Request $request, string $farmerId): FarmerProfile
    {
        $user = $request->user();
        $role = $user->roles->first()?->name ?? 'farmer';

        // Admin can access any farmer
        if ($role === 'admin') {
            return FarmerProfile::findOrFail($farmerId);
        }

        // Farmer can only access their own profile
        if ($role === 'farmer') {
            return FarmerProfile::where('id', $farmerId)
                ->where('user_id', $user->id)
                ->firstOrFail();
        }

        // Warehouse manager can access farmers whose warehouses they manage
        if ($role === 'warehouse') {
            $hasAccess = Warehouse::where('manager_user_id', $user->id)
                ->where('farmer_id', $farmerId)
                ->exists();
            if (!$hasAccess) {
                abort(403, 'You do not manage any warehouse for this farmer.');
            }
            return FarmerProfile::findOrFail($farmerId);
        }

        abort(403, 'Unauthorized to export this farmer data.');
    }

    /**
     * Collect all farmer data for export.
     */
    private function collectFarmerData(FarmerProfile $farmer): array
    {
        // Get warehouses owned by this farmer
        $warehouses = Warehouse::where('farmer_id', $farmer->id)->get();
        $warehouseIds = $warehouses->pluck('id');

        // Harvests
        $harvests = \App\Models\Harvest::where('farmer_id', $farmer->id)
            ->orderBy('harvest_date', 'desc')
            ->get()
            ->map(fn ($h) => [
                'crop_type' => $h->crop_type,
                'quantity_kg' => (float) $h->quantity_kg,
                'quality_grade' => $h->quality_grade,
                'harvest_date' => $h->harvest_date?->toDateString(),
                'status' => $h->status,
                'notes' => $h->notes,
            ])
            ->toArray();

        // Stocks in farmer's warehouses
        $stocks = Stock::whereIn('warehouse_id', $warehouseIds)
            ->orderBy('entry_date', 'desc')
            ->get()
            ->map(function ($s) use ($warehouses) {
                $wh = $warehouses->firstWhere('id', $s->warehouse_id);
                return [
                    'crop_type' => $s->crop_type,
                    'quantity_kg' => (float) $s->quantity_kg,
                    'warehouse_name' => $wh?->name ?? 'Unknown',
                    'entry_date' => $s->entry_date?->toDateString(),
                    'status' => $s->status,
                    'utilization_percentage' => $s->capacity_total > 0
                        ? round(($s->capacity_used / $s->capacity_total) * 100, 1)
                        : 0,
                ];
            })
            ->toArray();

        // Warehouses
        $warehousesData = $warehouses->map(fn ($w) => [
            'name' => $w->name,
            'region' => $w->region,
            'village' => $w->village,
            'capacity_total_kg' => (float) $w->capacity_total_kg,
            'capacity_used_kg' => (float) $w->capacity_used_kg,
            'status' => $w->status,
        ])->toArray();

        // Receipts
        $receipts = WarehouseReceipt::whereIn('warehouse_id', $warehouseIds)
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(fn ($r) => [
                'receipt_number' => $r->receipt_number,
                'crop_type' => $r->crop_type,
                'quantity_kg' => (float) $r->quantity_kg,
                'issue_date' => $r->issue_date?->toDateString(),
                'status' => $r->status,
            ])
            ->toArray();

        return [
            'harvests' => $harvests,
            'stocks' => $stocks,
            'warehouses' => $warehousesData,
            'receipts' => $receipts,
        ];
    }
}
