<?php

namespace Tests\Unit\Credibility;

use App\Credibility\Application\Services\CredibilityScoreService;
use App\Credibility\Domain\ValueObjects\CredibilityTier;
use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\ValueObjects\CropType;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Farm\Domain\ValueObjects\Quantity;
use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\Exceptions\FarmerNotFoundException;
use App\Farmer\Domain\Repositories\FarmerRepositoryInterface;
use App\Farmer\Domain\ValueObjects\CropTypes;
use App\Farmer\Domain\ValueObjects\FarmSize;
use App\Farmer\Domain\ValueObjects\Region;
use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CredibilityScoreServiceTest extends TestCase
{
    private function makeFarmer(string $id = 'farmer-1'): Farmer
    {
        return Farmer::register(
            id: $id,
            userId: 'user-1',
            farmName: 'Sunrise Farm',
            farmSize: FarmSize::fromHectares(2.5),
            crops: CropTypes::fromArray(['maize']),
            region: Region::fromString('Centre'),
            village: 'Obala',
        );
    }

    private function makeHarvest(
        string $id,
        string $farmerId,
        HarvestStatus $status,
        \DateTimeImmutable $harvestDate,
    ): Harvest {
        $harvest = Harvest::record(
            id: $id,
            farmerId: $farmerId,
            cropType: CropType::fromString('maize'),
            quantity: Quantity::fromKilograms(100),
            harvestDate: $harvestDate,
        );

        // Drive the harvest through its real state machine to reach the target status.
        if ($status === HarvestStatus::IN_TRANSIT || $status === HarvestStatus::STORED || $status === HarvestStatus::SOLD) {
            $harvest->sendToWarehouse('warehouse-1');
        }
        if ($status === HarvestStatus::STORED || $status === HarvestStatus::SOLD) {
            $harvest->storeInWarehouse();
        }
        if ($status === HarvestStatus::SOLD) {
            $harvest->markAsSold();
        }

        return $harvest;
    }

    public function test_throws_when_farmer_not_found(): void
    {
        $service = new CredibilityScoreService(
            farmerRepository: new class implements FarmerRepositoryInterface {
                public function save(Farmer $farmer): void {}
                public function findById(string $id): ?Farmer { return null; }
                public function findByUserId(string $userId): ?Farmer { return null; }
                public function findByRegion(Region $region): array { return []; }
                public function findAllActive(): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Farmer $farmer): void {}
                public function existsByUserId(string $userId): bool { return false; }
            },
            harvestRepository: new class implements HarvestRepositoryInterface {
                public function save(Harvest $harvest): void {}
                public function findById(string $id): ?Harvest { return null; }
                public function findByFarmerId(string $farmerId): array { return []; }
                public function findByWarehouseId(string $warehouseId): array { return []; }
                public function findByStatus(HarvestStatus $status): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Harvest $harvest): void {}
            },
            stockRepository: new class implements StockRepositoryInterface {
                public function save(Stock $stock): void {}
                public function findById(string $id): ?Stock { return null; }
                public function findByWarehouseId(string $warehouseId): array { return []; }
                public function findByHarvestId(string $harvestId): ?Stock { return null; }
                public function findByStatus(\App\Stock\Domain\ValueObjects\StockStatus $status): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Stock $stock): void {}
            },
        );

        $this->expectException(FarmerNotFoundException::class);
        $service->getScore('missing-farmer');
    }

    public function test_new_farmer_with_no_harvests_scores_zero_and_is_building_tier(): void
    {
        $farmer = $this->makeFarmer();

        $service = new CredibilityScoreService(
            farmerRepository: new class($farmer) implements FarmerRepositoryInterface {
                public function __construct(private readonly Farmer $farmer) {}
                public function save(Farmer $farmer): void {}
                public function findById(string $id): ?Farmer { return $this->farmer; }
                public function findByUserId(string $userId): ?Farmer { return $this->farmer; }
                public function findByRegion(Region $region): array { return [$this->farmer]; }
                public function findAllActive(): array { return [$this->farmer]; }
                public function findAll(): array { return [$this->farmer]; }
                public function delete(Farmer $farmer): void {}
                public function existsByUserId(string $userId): bool { return true; }
            },
            harvestRepository: new class implements HarvestRepositoryInterface {
                public function save(Harvest $harvest): void {}
                public function findById(string $id): ?Harvest { return null; }
                public function findByFarmerId(string $farmerId): array { return []; }
                public function findByWarehouseId(string $warehouseId): array { return []; }
                public function findByStatus(HarvestStatus $status): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Harvest $harvest): void {}
            },
            stockRepository: new class implements StockRepositoryInterface {
                public function save(Stock $stock): void {}
                public function findById(string $id): ?Stock { return null; }
                public function findByWarehouseId(string $warehouseId): array { return []; }
                public function findByHarvestId(string $harvestId): ?Stock { return null; }
                public function findByStatus(\App\Stock\Domain\ValueObjects\StockStatus $status): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Stock $stock): void {}
            },
        );

        $score = $service->getScore($farmer->getId());

        $this->assertSame(0, $score->getValue());
        $this->assertSame(CredibilityTier::BUILDING, $score->getTier());
        $this->assertSame(1, $score->getMaxFinancingTermYears());
    }

    public function test_active_farmer_with_stored_harvests_and_certified_stock_scores_higher(): void
    {
        $farmer = $this->makeFarmer();

        $harvests = [];
        for ($i = 1; $i <= 12; $i++) {
            $harvests[] = $this->makeHarvest(
                "harvest-{$i}",
                $farmer->getId(),
                HarvestStatus::STORED,
                new \DateTimeImmutable("-{$i} weeks"),
            );
        }

        $stocksByHarvestId = [];
        foreach ($harvests as $harvest) {
            $stocksByHarvestId[$harvest->getId()] = Stock::create(
                id: 'stock-' . $harvest->getId(),
                warehouseId: 'warehouse-1',
                harvestId: $harvest->getId(),
                cropType: $harvest->getCropType(),
                quantityKg: $harvest->getQuantity()->toKilograms(),
                capacity: Capacity::fromValues(100, 5000),
                entryDate: new \DateTimeImmutable(),
            );
        }

        $service = new CredibilityScoreService(
            farmerRepository: new class($farmer) implements FarmerRepositoryInterface {
                public function __construct(private readonly Farmer $farmer) {}
                public function save(Farmer $farmer): void {}
                public function findById(string $id): ?Farmer { return $this->farmer; }
                public function findByUserId(string $userId): ?Farmer { return $this->farmer; }
                public function findByRegion(Region $region): array { return [$this->farmer]; }
                public function findAllActive(): array { return [$this->farmer]; }
                public function findAll(): array { return [$this->farmer]; }
                public function delete(Farmer $farmer): void {}
                public function existsByUserId(string $userId): bool { return true; }
            },
            harvestRepository: new class($harvests) implements HarvestRepositoryInterface {
                public function __construct(private readonly array $harvests) {}
                public function save(Harvest $harvest): void {}
                public function findById(string $id): ?Harvest { return null; }
                public function findByFarmerId(string $farmerId): array { return $this->harvests; }
                public function findByWarehouseId(string $warehouseId): array { return $this->harvests; }
                public function findByStatus(HarvestStatus $status): array { return $this->harvests; }
                public function findAll(): array { return $this->harvests; }
                public function delete(Harvest $harvest): void {}
            },
            stockRepository: new class($stocksByHarvestId) implements StockRepositoryInterface {
                public function __construct(private readonly array $stocksByHarvestId) {}
                public function save(Stock $stock): void {}
                public function findById(string $id): ?Stock { return null; }
                public function findByWarehouseId(string $warehouseId): array { return array_values($this->stocksByHarvestId); }
                public function findByHarvestId(string $harvestId): ?Stock { return $this->stocksByHarvestId[$harvestId] ?? null; }
                public function findByStatus(\App\Stock\Domain\ValueObjects\StockStatus $status): array { return array_values($this->stocksByHarvestId); }
                public function findAll(): array { return array_values($this->stocksByHarvestId); }
                public function delete(Stock $stock): void {}
            },
        );

        $breakdown = $service->getBreakdown($farmer->getId());

        // 12 harvests within the last year against a 12/year baseline => 100% movement consistency.
        $this->assertEqualsWithDelta(100.0, $breakdown['categories']['movement_consistency']['raw_pct'], 0.01);
        // All harvests are STORED => 100% verified movements.
        $this->assertEqualsWithDelta(100.0, $breakdown['categories']['verified_movements']['raw_pct'], 0.01);
        // Repayment history is not wired to the Loans module yet, so it always contributes 0.
        $this->assertSame(0.0, $breakdown['categories']['repayment_history']['raw_pct']);

        // Weighted total should be strictly greater than the zero-harvest baseline.
        $this->assertGreaterThan(0, $breakdown['total_score']);
        $this->assertLessThanOrEqual(100, $breakdown['total_score']);
    }

    public function test_verified_movements_ratio_counts_only_stored_or_sold_harvests(): void
    {
        $farmer = $this->makeFarmer();

        $harvests = [
            $this->makeHarvest('h1', $farmer->getId(), HarvestStatus::STORED, new \DateTimeImmutable('-1 week')),
            $this->makeHarvest('h2', $farmer->getId(), HarvestStatus::SOLD, new \DateTimeImmutable('-2 weeks')),
            $this->makeHarvest('h3', $farmer->getId(), HarvestStatus::HARVESTED, new \DateTimeImmutable('-3 weeks')),
            $this->makeHarvest('h4', $farmer->getId(), HarvestStatus::IN_TRANSIT, new \DateTimeImmutable('-4 weeks')),
        ];

        $service = new CredibilityScoreService(
            farmerRepository: new class($farmer) implements FarmerRepositoryInterface {
                public function __construct(private readonly Farmer $farmer) {}
                public function save(Farmer $farmer): void {}
                public function findById(string $id): ?Farmer { return $this->farmer; }
                public function findByUserId(string $userId): ?Farmer { return $this->farmer; }
                public function findByRegion(Region $region): array { return [$this->farmer]; }
                public function findAllActive(): array { return [$this->farmer]; }
                public function findAll(): array { return [$this->farmer]; }
                public function delete(Farmer $farmer): void {}
                public function existsByUserId(string $userId): bool { return true; }
            },
            harvestRepository: new class($harvests) implements HarvestRepositoryInterface {
                public function __construct(private readonly array $harvests) {}
                public function save(Harvest $harvest): void {}
                public function findById(string $id): ?Harvest { return null; }
                public function findByFarmerId(string $farmerId): array { return $this->harvests; }
                public function findByWarehouseId(string $warehouseId): array { return $this->harvests; }
                public function findByStatus(HarvestStatus $status): array { return $this->harvests; }
                public function findAll(): array { return $this->harvests; }
                public function delete(Harvest $harvest): void {}
            },
            stockRepository: new class implements StockRepositoryInterface {
                public function save(Stock $stock): void {}
                public function findById(string $id): ?Stock { return null; }
                public function findByWarehouseId(string $warehouseId): array { return []; }
                public function findByHarvestId(string $harvestId): ?Stock { return null; }
                public function findByStatus(\App\Stock\Domain\ValueObjects\StockStatus $status): array { return []; }
                public function findAll(): array { return []; }
                public function delete(Stock $stock): void {}
            },
        );

        $breakdown = $service->getBreakdown($farmer->getId());

        // 2 of 4 harvests are STORED/SOLD => 50% verified movements.
        $this->assertEqualsWithDelta(50.0, $breakdown['categories']['verified_movements']['raw_pct'], 0.01);
    }

    #[DataProvider('tierBoundaryProvider')]
    public function test_tier_and_max_financing_term_boundaries(int $score, CredibilityTier $expectedTier, int $expectedMaxYears): void
    {
        $tier = CredibilityTier::fromScore($score);

        $this->assertSame($expectedTier, $tier);
        $this->assertSame($expectedMaxYears, $tier->maxFinancingTermYears());
    }

    /**
     * @return array<string, array{0: int, 1: CredibilityTier, 2: int}>
     */
    public static function tierBoundaryProvider(): array
    {
        return [
            'score 0 is building' => [0, CredibilityTier::BUILDING, 1],
            'score 39 is building' => [39, CredibilityTier::BUILDING, 1],
            'score 40 is established' => [40, CredibilityTier::ESTABLISHED, 5],
            'score 69 is established' => [69, CredibilityTier::ESTABLISHED, 5],
            'score 70 is strong' => [70, CredibilityTier::STRONG, 10],
            'score 84 is strong' => [84, CredibilityTier::STRONG, 10],
            'score 85 is high' => [85, CredibilityTier::HIGH, 20],
            'score 100 is high' => [100, CredibilityTier::HIGH, 20],
        ];
    }

    public function test_credibility_score_rejects_out_of_range_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \App\Credibility\Domain\ValueObjects\CredibilityScore::fromValue(101);
    }

    public function test_credibility_score_rejects_negative_values(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \App\Credibility\Domain\ValueObjects\CredibilityScore::fromValue(-1);
    }
}
