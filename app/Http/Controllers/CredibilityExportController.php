<?php

namespace App\Http\Controllers;

use App\Credibility\Application\Services\CredibilityScoreService;
use App\Credibility\Domain\ValueObjects\CredibilityScore;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CredibilityExportController extends Controller
{
    public function __construct(
        private readonly CredibilityScoreService $credibilityScoreService,
        private readonly FarmerRepositoryInterface $farmerRepository,
    ) {}

    /**
     * Download a branded agriAid credibility score report as PDF.
     * GET /api/admin/credibility-scores/export-pdf
     */
    public function exportPdf(): Response
    {
        $farmers = $this->collectFarmerScores();
        $generatedAt = now()->format('Y-m-d H:i');

        $pdf = Pdf::loadView('exports.credibility', [
            'farmers' => $farmers,
            'generatedAt' => $generatedAt,
        ]);

        $pdf->setPaper('A4', 'portrait');

        $fileName = 'agriaid-credibility-report-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($fileName);
    }

    /**
     * Download a branded agriAid credibility score report as CSV.
     * GET /api/admin/credibility-scores/export-csv
     */
    public function exportCsv(): StreamedResponse
    {
        $farmers = $this->collectFarmerScores();
        $generatedAt = now()->format('Y-m-d H:i');

        $fileName = 'agriaid-credibility-report-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($farmers, $generatedAt) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Branded header comments
            fwrite($handle, "# agriAid Platform - Credibility Score Report\n");
            fwrite($handle, "# Empowering Cameroon's Agricultural Future\n");
            fwrite($handle, "# Generated: {$generatedAt}\n");
            fwrite($handle, "# \n");
            fwrite($handle, "# The credibility score (0-100) is calculated using:\n");
            fwrite($handle, "#   - 30% Movement consistency/frequency\n");
            fwrite($handle, "#   - 25% Independently verified movements\n");
            fwrite($handle, "#   - 25% Repayment history\n");
            fwrite($handle, "#   - 10% Length of platform use\n");
            fwrite($handle, "#   - 10% Volume/value of certified stock\n");
            fwrite($handle, "#\n");

            // Data headers
            fputcsv($handle, [
                'Farm Name',
                'Region',
                'Village',
                'Score',
                'Tier',
                'Tier Label',
                'Max Financing Term (Years)',
                'Movement Consistency %',
                'Verified Movements %',
                'Repayment History %',
                'Platform Use Length %',
                'Certified Stock Volume %',
            ]);

            // Data rows
            foreach ($farmers as $farmer) {
                fputcsv($handle, [
                    $farmer['farm_name'],
                    $farmer['region'],
                    $farmer['village'],
                    $farmer['score'] ?? '',
                    $farmer['tier'] ?? 'unavailable',
                    $farmer['tier_label'] ?? '',
                    $farmer['max_financing_term_years'] ?? '',
                    $farmer['movement_consistency_pct'] ?? '',
                    $farmer['verified_movements_pct'] ?? '',
                    $farmer['repayment_history_pct'] ?? '',
                    $farmer['platform_use_length_pct'] ?? '',
                    $farmer['certified_stock_volume_pct'] ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Collect all farmers' credibility scores and category breakdowns.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectFarmerScores(): array
    {
        $farmers = $this->farmerRepository->findAll();

        $items = array_map(function ($farmer) {
            $base = [
                'farm_name' => $farmer->getFarmName(),
                'region' => $farmer->getRegion()->toString(),
                'village' => $farmer->getVillage(),
            ];

            try {
                $breakdown = $this->credibilityScoreService->getBreakdown($farmer->getId());
                $score = CredibilityScore::fromValue($breakdown['total_score']);
                $categories = $breakdown['categories'];

                return [
                    ...$base,
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
                return [
                    ...$base,
                    'score' => null,
                    'tier' => null,
                    'tier_label' => 'unavailable',
                    'max_financing_term_years' => null,
                    'movement_consistency_pct' => null,
                    'verified_movements_pct' => null,
                    'repayment_history_pct' => null,
                    'platform_use_length_pct' => null,
                    'certified_stock_volume_pct' => null,
                ];
            }
        }, $farmers);

        return $items;
    }
}
