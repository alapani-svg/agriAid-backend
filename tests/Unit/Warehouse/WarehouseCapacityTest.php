<?php

namespace Tests\Unit\Warehouse;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\ValueObjects\CropType;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Farm\Domain\ValueObjects\Quantity;
use App\Stock\Application\Services\AutoUpdateStockOnHarvestStored;
use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\StockStatus;
use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Exceptions\InsufficientWarehouseCapacityException;
use App\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class WarehouseCapacityTest extends TestCase
{
    // -----------------------------------------------------------------
    // Warehouse entity: canAccept() / getAvailableCapacity()
    // -----------------------------------------------------------------

    public function test_warehouse_accepts_stock_within_capacity(): void
    {
        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        $this->assertTrue($warehouse->canAccept(currentlyUsedKg: 400, additionalKg: 500));
        $this->assertSame(600.0, $warehouse->getAvailableCapacity(400));
    }

    public function test_warehouse_rejects_stock_exceeding_capacity(): void
    {
        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        $this->assertFalse($warehouse->canAccept(currentlyUsedKg: 800, additionalKg: 300));
    }

    public function test_warehouse_accepts_stock_exactly_at_capacity(): void
    {
        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        $this->assertTrue($warehouse->canAccept(currentlyUsedKg: 700, additionalKg: 300));
    }

    public function test_inactive_warehouse_cannot_accept_any_stock(): void
    {
        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );
        $warehouse->deactivate();

        $this->assertFalse($warehouse->canAccept(currentlyUsedKg: 0, additionalKg: 1));
    }

    public function test_registering_a_warehouse_with_non_positive_capacity_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 0,
        );
    }

    // -----------------------------------------------------------------
    // AutoUpdateStockOnHarvestStored: real capacity-enforcement use case
    // -----------------------------------------------------------------

    private function harvestRepositoryWith(Harvest $harvest): HarvestRepositoryInterface
    {
        return new class($harvest) implements HarvestRepositoryInterface {
            public function __construct(private readonly Harvest $harvest) {}
            public function save(Harvest $harvest): void {}
            public function findById(string $id): ?Harvest { return $this->harvest; }
            public function findByFarmerId(string $farmerId): array { return [$this->harvest]; }
            public function findByWarehouseId(string $warehouseId): array { return [$this->harvest]; }
            public function findByStatus(HarvestStatus $status): array { return [$this->harvest]; }
            public function findAll(): array { return [$this->harvest]; }
            public function delete(Harvest $harvest): void {}
        };
    }

    private function warehouseRepositoryWith(?Warehouse $warehouse): WarehouseRepositoryInterface
    {
        return new class($warehouse) implements WarehouseRepositoryInterface {
            public function __construct(private readonly ?Warehouse $warehouse) {}
            public function save(Warehouse $warehouse): void {}
            public function findById(string $id): ?Warehouse { return $this->warehouse; }
            public function findByRegion(string $region): array { return array_filter([$this->warehouse]); }
            public function findAllActive(): array { return array_filter([$this->warehouse]); }
            public function findByManagerUserId(string $managerUserId): array { return array_filter([$this->warehouse]); }
            public function delete(Warehouse $warehouse): void {}
        };
    }

    /**
     * @param Stock[] $existingStocks
     */
    private function stockRepositoryWith(array $existingStocks): StockRepositoryInterface
    {
        return new class($existingStocks) implements StockRepositoryInterface {
            public array $saved = [];

            public function __construct(private readonly array $existingStocks) {}
            public function save(Stock $stock): void { $this->saved[] = $stock; }
            public function findById(string $id): ?Stock { return null; }
            public function findByWarehouseId(string $warehouseId): array { return $this->existingStocks; }
            public function findByHarvestId(string $harvestId): ?Stock { return null; }
            public function findByStatus(StockStatus $status): array { return $this->existingStocks; }
            public function findAll(): array { return $this->existingStocks; }
            public function delete(Stock $stock): void {}
        };
    }

    public function test_auto_update_stock_creates_stock_when_capacity_is_available(): void
    {
        $harvest = Harvest::record(
            id: 'h1',
            farmerId: 'farmer-1',
            cropType: CropType::fromString('maize'),
            quantity: Quantity::fromKilograms(300),
            harvestDate: new \DateTimeImmutable(),
        );

        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        $stockRepository = $this->stockRepositoryWith([]); // empty warehouse so far

        $service = new AutoUpdateStockOnHarvestStored(
            stockRepository: $stockRepository,
            harvestRepository: $this->harvestRepositoryWith($harvest),
            warehouseRepository: $this->warehouseRepositoryWith($warehouse),
        );

        $stock = $service->execute('h1', 'w1');

        $this->assertSame(300.0, $stock->getQuantityKg());
        $this->assertSame(300.0, $stock->getCapacity()->getUsed());
        $this->assertSame(1000.0, $stock->getCapacity()->getTotal());
        $this->assertCount(1, $stockRepository->saved);
    }

    public function test_auto_update_stock_throws_when_capacity_is_insufficient(): void
    {
        $harvest = Harvest::record(
            id: 'h1',
            farmerId: 'farmer-1',
            cropType: CropType::fromString('maize'),
            quantity: Quantity::fromKilograms(500),
            harvestDate: new \DateTimeImmutable(),
        );

        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        // Warehouse already holds 800kg of maize; the incoming 500kg would exceed capacity.
        $existingStock = Stock::create(
            id: 'existing-stock',
            warehouseId: 'w1',
            harvestId: null,
            cropType: CropType::fromString('maize'),
            quantityKg: 800,
            capacity: \App\Stock\Domain\ValueObjects\Capacity::fromValues(800, 1000),
            entryDate: new \DateTimeImmutable(),
        );

        $service = new AutoUpdateStockOnHarvestStored(
            stockRepository: $this->stockRepositoryWith([$existingStock]),
            harvestRepository: $this->harvestRepositoryWith($harvest),
            warehouseRepository: $this->warehouseRepositoryWith($warehouse),
        );

        $this->expectException(InsufficientWarehouseCapacityException::class);
        $service->execute('h1', 'w1');
    }

    public function test_auto_update_stock_only_counts_other_crop_types_separately(): void
    {
        $harvest = Harvest::record(
            id: 'h1',
            farmerId: 'farmer-1',
            cropType: CropType::fromString('rice'),
            quantity: Quantity::fromKilograms(500),
            harvestDate: new \DateTimeImmutable(),
        );

        $warehouse = Warehouse::register(
            id: 'w1',
            name: 'Central Silo',
            region: 'Centre',
            capacityTotalKg: 1000,
        );

        // 800kg of maize already stored does not compete with the new rice's per-crop capacity math,
        // but does share the warehouse's overall physical capacity ceiling.
        $existingMaizeStock = Stock::create(
            id: 'existing-maize-stock',
            warehouseId: 'w1',
            harvestId: null,
            cropType: CropType::fromString('maize'),
            quantityKg: 800,
            capacity: \App\Stock\Domain\ValueObjects\Capacity::fromValues(800, 1000),
            entryDate: new \DateTimeImmutable(),
        );

        $service = new AutoUpdateStockOnHarvestStored(
            stockRepository: $this->stockRepositoryWith([$existingMaizeStock]),
            harvestRepository: $this->harvestRepositoryWith($harvest),
            warehouseRepository: $this->warehouseRepositoryWith($warehouse),
        );

        $stock = $service->execute('h1', 'w1');

        // Per-crop capacity used for rice starts fresh at 0 + 500 = 500, well within total capacity.
        $this->assertSame(500.0, $stock->getCapacity()->getUsed());
    }

    public function test_auto_update_stock_throws_when_warehouse_not_found(): void
    {
        $harvest = Harvest::record(
            id: 'h1',
            farmerId: 'farmer-1',
            cropType: CropType::fromString('maize'),
            quantity: Quantity::fromKilograms(100),
            harvestDate: new \DateTimeImmutable(),
        );

        $service = new AutoUpdateStockOnHarvestStored(
            stockRepository: $this->stockRepositoryWith([]),
            harvestRepository: $this->harvestRepositoryWith($harvest),
            warehouseRepository: $this->warehouseRepositoryWith(null),
        );

        $this->expectException(WarehouseNotFoundException::class);
        $service->execute('h1', 'missing-warehouse');
    }
}
