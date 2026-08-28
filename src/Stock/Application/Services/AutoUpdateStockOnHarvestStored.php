<?php

namespace App\Stock\Application\Services;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use App\Warehouse\Domain\Exceptions\WarehouseNotFoundException;
use App\Warehouse\Domain\Exceptions\InsufficientWarehouseCapacityException;
use App\Models\Stock as EloquentStock;
use App\Models\Farmer;
use Illuminate\Support\Str;

class AutoUpdateStockOnHarvestStored
{
    /** Default market price per kg (FCFA) per crop type. */
    private const DEFAULT_PRICES_FCFA = [
        'maize' => 250,
        'cassava' => 200,
        'yam' => 400,
        'rice' => 500,
        'cocoa' => 1200,
        'coffee' => 1500,
        'groundnut' => 600,
        'beans' => 500,
        'plantain' => 300,
        'tomato' => 350,
        'pepper' => 800,
        'onion' => 400,
        'potato' => 300,
        'sorghum' => 250,
        'millet' => 300,
    ];

    private const DEFAULT_PRICE_FCFA = 300;

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

        // Copy the harvest photo onto the stock so it shows in the store
        if ($harvest->getPhotoPath()) {
            $stock->attachPhotoVerification(
                photoPath: $harvest->getPhotoPath(),
                aiEstimatedQuantityKg: $harvest->getAiEstimatedQuantityKg(),
                aiAnalysisNotes: $harvest->getAiAnalysisNotes(),
                verificationStatus: $harvest->getVerificationStatus(),
            );
        }

        $this->stockRepository->save($stock);

        // Auto-publish to the warehouse's linked store
        $this->autoPublishToStore($stock, $warehouseId, $harvest);

        return $stock;
    }

    /**
     * Set default market price, seller info, and link to the warehouse's store
     * so the stock appears on the store immediately when a harvest is stored.
     */
    private function autoPublishToStore(Stock $stock, string $warehouseId, Harvest $harvest): void
    {
        $eloquentStock = EloquentStock::find($stock->getId());
        if ($eloquentStock === null) {
            return;
        }

        $cropType = $harvest->getCropType()->toString();
        $defaultPrice = self::DEFAULT_PRICES_FCFA[$cropType] ?? self::DEFAULT_PRICE_FCFA;

        $farmer = Farmer::find($harvest->getFarmerId());
        $farmName = $farmer?->farm_name;

        // Link the stock to the warehouse's store
        $store = \App\Models\Store::where('warehouse_id', $warehouseId)->first();

        $eloquentStock->store_id = $store?->id;
        $eloquentStock->price_per_kg = $defaultPrice;
        $eloquentStock->currency = 'FCFA';
        $eloquentStock->unit = 'kg';
        $eloquentStock->seller_id = $harvest->getFarmerId();
        $eloquentStock->origin = $farmName ?? null;
        $eloquentStock->quality_grade = $eloquentStock->quality_grade ?? 'A';
        $eloquentStock->is_urgent_sale = false;
        $eloquentStock->flash_discount_percent = 0;
        $eloquentStock->save();
    }
}

