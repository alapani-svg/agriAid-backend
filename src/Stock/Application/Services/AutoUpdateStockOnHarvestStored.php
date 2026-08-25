<?php

namespace App\Stock\Application\Services;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use App\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use App\Warehouse\Domain\Exceptions\InsufficientWarehouseCapacityException;
use Illuminate\Support\Str;

class AutoUpdateStockOnHarvestStored
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly HarvestRepositoryInterface $harvestRepository,
        private readonly WarehouseRepositoryInterface $warehouseRepository,
    ) {}

    public function execute(
        string $harvestId,
        string $warehouseId,
    ): Stock {
        $harvest = $this->harvestRepository->findById($harvestId);

        if ($harvest === null) {
            throw new \DomainException("Harvest not found: {$harvestId}");
        }

        $warehouse = $this->warehouseRepository->findById($warehouseId);

        if ($warehouse === null) {
            throw new WarehouseNotFoundException("Warehouse not found: {$warehouseId}");
        }

        // Calculate current capacity used in warehouse for this crop type
        $existingStocks = $this->stockRepository->findByWarehouseId($warehouseId);
        $currentCapacityUsed = 0.0;

        foreach ($existingStocks as $stock) {
            if ($stock->getCropType()->toString() === $harvest->getCropType()->toString() && $stock->isInStock()) {
                $currentCapacityUsed += $stock->getCapacity()->getUsed();
            }
        }

        $additionalKg = $harvest->getQuantity()->toKilograms();

        if (!$warehouse->canAccept($currentCapacityUsed, $additionalKg)) {
            throw new InsufficientWarehouseCapacityException(
                "Insufficient warehouse capacity. Required: " . ($currentCapacityUsed + $additionalKg)
                . "kg, Available: {$warehouse->getAvailableCapacity($currentCapacityUsed)}kg"
            );
        }

        $newCapacityUsed = $currentCapacityUsed + $additionalKg;

        // Create or update stock
        $stock = Stock::create(
            id: (string) Str::uuid(),
            warehouseId: $warehouseId,
            harvestId: $harvestId,
            cropType: $harvest->getCropType(),
            quantityKg: $additionalKg,
            capacity: Capacity::fromValues($newCapacityUsed, $warehouse->getCapacityTotalKg()),
            entryDate: new \DateTimeImmutable(),
            notes: "Auto-created from stored harvest",
        );

        $this->stockRepository->save($stock);

        return $stock;
    }
}
