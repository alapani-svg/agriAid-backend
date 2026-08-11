<?php

namespace App\Stock\Application\Services;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Farm\Domain\ValueObjects\CropType;
use Illuminate\Support\Str;

class AutoUpdateStockOnHarvestStored
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly HarvestRepositoryInterface $harvestRepository
    ) {}

    public function execute(
        string $harvestId,
        string $warehouseId,
        float $warehouseCapacityTotal,
    ): Stock {
        $harvest = $this->harvestRepository->findById($harvestId);
        
        if ($harvest === null) {
            throw new \DomainException("Harvest not found: {$harvestId}");
        }

        // Calculate current capacity used in warehouse for this crop type
        $existingStocks = $this->stockRepository->findByWarehouseId($warehouseId);
        $currentCapacityUsed = 0.0;
        
        foreach ($existingStocks as $stock) {
            if ($stock->getCropType()->toString() === $harvest->getCropType()->toString() && $stock->isInStock()) {
                $currentCapacityUsed += $stock->getCapacity()->getUsed();
            }
        }

        // Calculate new capacity
        $newCapacityUsed = $currentCapacityUsed + $harvest->getQuantity()->toKilograms();
        
        if ($newCapacityUsed > $warehouseCapacityTotal) {
            throw new \DomainException("Insufficient warehouse capacity. Required: {$newCapacityUsed}kg, Available: {$warehouseCapacityTotal}kg");
        }

        // Create or update stock
        $stock = Stock::create(
            id: (string) Str::uuid(),
            warehouseId: $warehouseId,
            harvestId: $harvestId,
            cropType: $harvest->getCropType(),
            quantityKg: $harvest->getQuantity()->toKilograms(),
            capacity: Capacity::fromValues($newCapacityUsed, $warehouseCapacityTotal),
            entryDate: new \DateTimeImmutable(),
            notes: "Auto-created from stored harvest",
        );

        $this->stockRepository->save($stock);

        return $stock;
    }
}
