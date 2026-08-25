<?php

namespace Tests\Feature\Warehouse;

use App\Models\User;
use App\Notifications\Domain\Repositories\NotificationRepositoryInterface;
use App\Notifications\Domain\ValueObjects\NotificationType;
use App\Warehouse\Application\Services\WarehouseCapacityAlertService;
use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseCapacityAlertFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_manager_receives_a_capacity_warning_notification(): void
    {
        $manager = User::factory()->create();

        $warehouseRepository = $this->app->make(WarehouseRepositoryInterface::class);
        $warehouseRepository->save(
            Warehouse::register(
                id: (string) \Illuminate\Support\Str::uuid(),
                name: 'Feature Test Silo',
                region: 'Centre',
                capacityTotalKg: 1000,
                managerUserId: (string) $manager->id,
            )
        );

        $warehouseId = $warehouseRepository->findByManagerUserId((string) $manager->id)[0]->getId();

        $stockRepository = $this->app->make(\App\Stock\Domain\Repositories\StockRepositoryInterface::class);
        $stockRepository->save(
            \App\Stock\Domain\Entities\Stock::create(
                id: (string) \Illuminate\Support\Str::uuid(),
                warehouseId: $warehouseId,
                harvestId: null,
                cropType: \App\Farm\Domain\ValueObjects\CropType::fromString('maize'),
                quantityKg: 800, // 80% utilization: crosses the 75% warning threshold
                capacity: \App\Stock\Domain\ValueObjects\Capacity::fromValues(800, 1000),
                entryDate: new \DateTimeImmutable(),
            )
        );

        $service = $this->app->make(WarehouseCapacityAlertService::class);
        $service->checkAndNotify($warehouseId);

        $notifications = $this->app->make(NotificationRepositoryInterface::class);
        $result = $notifications->paginateForUser((string) $manager->id, 20, 1);

        $this->assertCount(1, $result['data']);
        $this->assertSame(
            NotificationType::WAREHOUSE_CAPACITY_WARNING->value,
            $result['data'][0]->getType()->value,
        );
    }
}
