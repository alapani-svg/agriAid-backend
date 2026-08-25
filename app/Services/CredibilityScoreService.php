<?php

namespace App\Services;

class CredibilityScoreService
{
    public static function breakdown(array $loanData): array
    {
        $consistency = 250;
        $verifiedMovements = !empty($loanData['warehouse_receipt_id']) ? 250 : 80;
        $priorRepayments = 150;
        $platformContinuity = 100;
        $certifiedStockVolume = !empty($loanData['collateral_cert_no']) ? 100 : 30;

        $total = min(1000, $consistency + $verifiedMovements + $priorRepayments + $platformContinuity + $certifiedStockVolume);

        return [
            'consistency' => $consistency,
            'verifiedMovements' => $verifiedMovements,
            'priorRepayments' => $priorRepayments,
            'platformContinuity' => $platformContinuity,
            'certifiedStockVolume' => $certifiedStockVolume,
            'totalScore' => $total,
            'ratingTier' => self::tier($total),
            'maxEligibleTermYears' => self::maxTerm($total),
        ];
    }

    public static function score(array $loanData): int
    {
        return (int) round(self::breakdown($loanData)['totalScore'] / 10);
    }

    private static function tier(int $total): string
    {
        return match (true) {
            $total >= 800 => 'EXCELLENT',
            $total >= 650 => 'GOOD',
            $total >= 450 => 'MODERATE',
            default => 'BUILDING',
        };
    }

    private static function maxTerm(int $total): int
    {
        return match (true) {
            $total >= 800 => 20,
            $total >= 650 => 15,
            $total >= 450 => 10,
            default => 5,
        };
    }
}
