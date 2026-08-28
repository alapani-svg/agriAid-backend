<?php

namespace App\Http\Controllers;

use App\Models\RegionalReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegionalReportExportController extends Controller
{
    /**
     * Download a branded agriAid regional report as PDF.
     * GET /api/regional-reports/export-pdf
     */
    public function exportPdf(): Response
    {
        $reports = RegionalReport::orderByDesc('created_at')->get();
        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('exports.regional-report', [
            'reports' => $reports,
            'generatedAt' => $generatedAt,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $fileName = 'agriaid-regional-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download a branded agriAid single regional report as PDF.
     * GET /api/regional-reports/{id}/export-pdf
     */
    public function exportSinglePdf(int $id): Response
    {
        $report = RegionalReport::findOrFail($id);
        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('exports.regional-report-single', [
            'report' => $report,
            'generatedAt' => $generatedAt,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $regionSlug = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($report->region ?? 'region'));
        $fileName = 'agriaid-regional-report-' . $regionSlug . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download a branded agriAid regional report as CSV.
     * GET /api/regional-reports/export-csv
     */
    public function exportCsv(): StreamedResponse
    {
        $reports = RegionalReport::orderByDesc('created_at')->get();
        $generatedAt = now()->format('Y-m-d H:i');

        $fileName = 'agriaid-regional-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($reports, $generatedAt) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Branded header comments
            fwrite($handle, "# agriAid Platform - Regional Report\n");
            fwrite($handle, "# Empowering Cameroon's Agricultural Future\n");
            fwrite($handle, "# Generated: {$generatedAt}\n");
            fwrite($handle, "# \n");

            // Data headers
            fputcsv($handle, [
                'Region',
                'City',
                'Report Type',
                'Period Start',
                'Period End',
                'Total Production (MT)',
                'Warehouse Stock (MT)',
                'Financing Volume (FCFA)',
                'Active Farmers',
            ]);

            // Data rows
            foreach ($reports as $report) {
                fputcsv($handle, [
                    $report->region,
                    $report->city ?? '',
                    $report->report_type ?? '',
                    $report->period_start ? $report->period_start->format('Y-m-d') : '',
                    $report->period_end ? $report->period_end->format('Y-m-d') : '',
                    $report->total_production_mt ?? '',
                    $report->warehouse_stock_mt ?? '',
                    $report->financing_volume_fcfa ?? '',
                    $report->active_farmers ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
