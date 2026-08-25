<?php

namespace App\Credibility\Application\Services;

use App\Credibility\Domain\ValueObjects\CredibilityScore;
use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Farmer\Domain\Exceptions\FarmerNotFoundException;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Stock\Domain\Repositories\StockRepositoryInterface;

/**
 * Computes a farmer's credibility score (0-100) used to determine financing eligibility.
 *
 * Weighting (per product spec):
 *  - 30% movement consistency/frequency
 *  - 25% independently verified movements
 *  - 25% repayment history
 *  - 10% length of platform use
 *  - 10% volume/value of certified stock
 *
 * NOTE: Repayment history depends on the Loans module (owned separately). Until that
 * data source exists, its weight contributes 0 rather than being redistributed, so
 * scores will trend low until loan/repayment data is wired in — this is intentional
 * and documented, not a bug.
 */
class CredibilityScoreService
{
    private const WEIGHT_MOVEMENT_CONSISTENCY = 0.30;
    private const WEIGHT_VERIFIED_MOVEMENTS = 0.25;
    private const WEIGHT_REPAYMENT_HISTORY = 0.25;
    private const WEIGHT_PLATFORM_USE_LENGTH = 0.10;
    private const WEIGHT_CERTIFIED_STOCK_VOLUME = 0.10;

    /** Harvests/year considered "fully consistent" (100%). */
    private const MOVEMENT_FREQUENCY_BASELINE_PER_YEAR = 12;

    /** Months of platform tenure considered "fully established" (100%). */
    private const PLATFORM_USE_BASELINE_MONTHS = 24;

    /** Certified stock (kg) considered "fully certified" (100%). */
    private const CERTIFIED_STOCK_BASELINE_KG = 5000.0;

    public function __construct(
        private readonly FarmerRepositoryInterface $farmerRepository,
        private readonly HarvestRepositoryInterface $harvestRepository,
        private readonly StockRepositoryInterface $stockRepository,
    ) {}

    /**
     * Public contractual interface consumed by the Loans module.
     */
    public function getScore(string $farmerId): CredibilityScore
    {
        return CredibilityScore::fromValue($this->getBreakdown($farmerId)['total_score']);
    }

    /**
     * Returns the real, unweighted-and-weighted breakdown behind the score, for
     * display purposes (e.g. the farmer dashboard). Not part of the Loans module
     * contract — use getScore() for that.
     *
     * @return array{
     *     total_score: int,
     *     categories: array<string, array{label: string, raw_pct: float, weight_pct: int, weighted_pct: float}>,
     * }
     */
    public function getBreakdown(string $farmerId): array
    {
        $farmer = $this->farmerRepository->findById($farmerId);

        if ($farmer === null) {
            throw new FarmerNotFoundException("Farmer not found: {$farmerId}");
        }

        $harvests = $this->harvestRepository->findByFarmerId($farmerId);

        $movementConsistency = $this->calculateMovementConsistency($harvests);
        $verifiedMovements = $this->calculateVerifiedMovements($harvests);
        $repaymentHistory = $this->calculateRepaymentHistory($farmerId);
        $platformUseLength = $this->calculatePlatformUseLength($farmer->getCreatedAt());
        $certifiedStockVolume = $this->calculateCertifiedStockVolume($harvests);

        $categories = [
            'movement_consistency' => [
                'label' => 'Movement consistency',
                'raw_pct' => $movementConsistency,
                'weight_pct' => (int) round(self::WEIGHT_MOVEMENT_CONSISTENCY * 100),
                'weighted_pct' => $movementConsistency * self::WEIGHT_MOVEMENT_CONSISTENCY,
            ],
            'verified_movements' => [
                'label' => 'Verified movements',
                'raw_pct' => $verifiedMovements,
                'weight_pct' => (int) round(self::WEIGHT_VERIFIED_MOVEMENTS * 100),
                'weighted_pct' => $verifiedMovements * self::WEIGHT_VERIFIED_MOVEMENTS,
            ],
            'repayment_history' => [
                'label' => 'Repayment history',
                'raw_pct' => $repaymentHistory,
                'weight_pct' => (int) round(self::WEIGHT_REPAYMENT_HISTORY * 100),
                'weighted_pct' => $repaymentHistory * self::WEIGHT_REPAYMENT_HISTORY,
            ],
            'platform_use_length' => [
                'label' => 'Platform use length',
                'raw_pct' => $platformUseLength,
                'weight_pct' => (int) round(self::WEIGHT_PLATFORM_USE_LENGTH * 100),
                'weighted_pct' => $platformUseLength * self::WEIGHT_PLATFORM_USE_LENGTH,
            ],
            'certified_stock_volume' => [
                'label' => 'Certified stock volume',
                'raw_pct' => $certifiedStockVolume,
                'weight_pct' => (int) round(self::WEIGHT_CERTIFIED_STOCK_VOLUME * 100),
                'weighted_pct' => $certifiedStockVolume * self::WEIGHT_CERTIFIED_STOCK_VOLUME,
            ],
        ];

        $weighted = array_sum(array_column($categories, 'weighted_pct'));
        $totalScore = (int) round(min(100.0, max(0.0, $weighted)));

        return [
            'total_score' => $totalScore,
            'categories' => $categories,
        ];
    }

    /**
     * @param Harvest[] $harvests
     */
    private function calculateMovementConsistency(array $harvests): float
    {
        if (empty($harvests)) {
            return 0.0;
        }

        $oneYearAgo = new \DateTimeImmutable('-1 year');
        $recentCount = 0;

        foreach ($harvests as $harvest) {
            if ($harvest->getHarvestDate() >= $oneYearAgo) {
                $recentCount++;
            }
        }

        return min(100.0, ($recentCount / self::MOVEMENT_FREQUENCY_BASELINE_PER_YEAR) * 100);
    }

    /**
     * @param Harvest[] $harvests
     */
    private function calculateVerifiedMovements(array $harvests): float
    {
        if (empty($harvests)) {
            return 0.0;
        }

        $verifiedCount = 0;

        foreach ($harvests as $harvest) {
            if ($harvest->getStatus() === HarvestStatus::STORED || $harvest->getStatus() === HarvestStatus::SOLD) {
                $verifiedCount++;
            }
        }

        return ($verifiedCount / count($harvests)) * 100;
    }

    /**
     * Placeholder pending the Loans module's repayment data. Returns 0 until that
     * integration exists — see class-level note.
     */
    private function calculateRepaymentHistory(string $farmerId): float
    {
        return 0.0;
    }

    private function calculatePlatformUseLength(\DateTimeImmutable $farmerCreatedAt): float
    {
        $months = $farmerCreatedAt->diff(new \DateTimeImmutable())->y * 12
            + $farmerCreatedAt->diff(new \DateTimeImmutable())->m;

        return min(100.0, ($months / self::PLATFORM_USE_BASELINE_MONTHS) * 100);
    }

    /**
     * @param Harvest[] $harvests
     */
    private function calculateCertifiedStockVolume(array $harvests): float
    {
        $totalKg = 0.0;

        foreach ($harvests as $harvest) {
            $stock = $this->stockRepository->findByHarvestId($harvest->getId());

            if ($stock !== null && $stock->isInStock()) {
                $totalKg += $stock->getQuantityKg();
            }
        }

        return min(100.0, ($totalKg / self::CERTIFIED_STOCK_BASELINE_KG) * 100);
    }
}
