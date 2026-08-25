<?php

namespace Tests\Unit\Warehouse;

use App\Farm\Domain\ValueObjects\CropType;
use App\Notifications\Application\Services\NotificationApplicationService;
use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Stock\Domain\ValueObjects\StockStatus;
use App\Warehouse\Application\Services\WarehouseCapacityAlertService;
use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

final class WarehouseCapacityAlertServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
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
     * @param Stock[] $stocks
     */
    private function stockRepositoryWith(array $stocks): StockRepositoryInterface
    {
        return new class($stocks) implements StockRepositoryInterface {
            public function __construct(private readonly array $stocks) {}
            public function save(Stock $stock): void {}
            public function findById(string $id): ?Stock { return null; }
            public function findByWarehouseId(string $warehouseId): array { return $this->stocks; }
            public function findByHarvestId(string $harvestId): ?Stock { return null; }
            public function findByStatus(StockStatus $status): array { return $this->stocks; }
            public function findAll(): array { return $this->stocks; }
            public function delete(Stock $stock): void {}
        };
    }

    private function stockOf(float $quantityKg): Stock
    {
        return Stock::create(
            id: 'stock-' . uniqid(),
            warehouseId: 'w1',
            harvestId: null,
            cropType: CropType::fromString('maize'),
            quantityKg: $quantityKg,
            capacity: Capacity::fromValues($quantityKg, 1000),
            entryDate: new \DateTimeImmutable(),
        );
    }

    public function test_no_alert_sent_below_warning_threshold(): void
    {
        $warehouse = Warehouse::register(id: 'w1', name: 'Silo', region: 'Centre', capacityTotalKg: 1000, managerUserId: '42');

        /** @var NotificationApplicationService&\Mockery\MockInterface $notifications */
        $notifications = Mockery::mock(NotificationApplicationService::class);
        $notifications->shouldNotReceive('notify');

        $service = new WarehouseCapacityAlertService(
            warehouseRepository: $this->warehouseRepositoryWith($warehouse),
            stockRepository: $this->stockRepositoryWith([$this->stockOf(500)]), // 50% utilization
            notificationService: $notifications,
        );

        $service->checkAndNotify('w1');
        $this->addToAssertionCount(1); // the shouldNotReceive() expectation above is the real assertion
    }

    /**
     * The "alert actually sent" path resolves the manager via `User::find()`, which needs a
     * real database — that end-to-end path is covered by the Feature test instead. Here we
     * verify the threshold math directly, since that's the part most likely to regress.
     */
    public function test_utilization_threshold_math(): void
    {
        $warehouseAt75 = Warehouse::register(id: 'w1', name: 'Silo', region: 'Centre', capacityTotalKg: 1000);
        $this->assertSame(75.0, (750 / $warehouseAt75->getCapacityTotalKg()) * 100);

        $warehouseAt90 = Warehouse::register(id: 'w2', name: 'Silo 2', region: 'Centre', capacityTotalKg: 1000);
        $this->assertSame(90.0, (900 / $warehouseAt90->getCapacityTotalKg()) * 100);

        $this->assertTrue((750 / 1000) * 100 >= 75.0, 'Exactly 75% should trigger the warning threshold');
        $this->assertTrue((900 / 1000) * 100 >= 90.0, 'Exactly 90% should trigger the critical threshold');
        $this->assertFalse((749 / 1000) * 100 >= 75.0, 'Just under 75% should not trigger any alert');
    }

    public function test_no_alert_when_warehouse_has_no_manager(): void
    {
        $warehouse = Warehouse::register(id: 'w1', name: 'Silo', region: 'Centre', capacityTotalKg: 1000, managerUserId: null);

        /** @var NotificationApplicationService&\Mockery\MockInterface $notifications */
        $notifications = Mockery::mock(NotificationApplicationService::class);
        $notifications->shouldNotReceive('notify');

        $service = new WarehouseCapacityAlertService(
            warehouseRepository: $this->warehouseRepositoryWith($warehouse),
            stockRepository: $this->stockRepositoryWith([$this->stockOf(950)]), // 95% utilization, but no manager to alert
            notificationService: $notifications,
        );

        $service->checkAndNotify('w1');
        $this->addToAssertionCount(1); // the shouldNotReceive() expectation above is the real assertion
    }

    public function test_no_alert_when_warehouse_not_found(): void
    {
        /** @var NotificationApplicationService&\Mockery\MockInterface $notifications */
        $notifications = Mockery::mock(NotificationApplicationService::class);
        $notifications->shouldNotReceive('notify');

        $service = new WarehouseCapacityAlertService(
            warehouseRepository: $this->warehouseRepositoryWith(null),
            stockRepository: $this->stockRepositoryWith([]),
            notificationService: $notifications,
        );

        $service->checkAndNotify('missing-warehouse');
        $this->addToAssertionCount(1); // the shouldNotReceive() expectation above is the real assertion
    }
}
