<?php

namespace App\Stock\Application\Services;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\Repositories\StockRepositoryInterface;
use App\Stock\Domain\ValueObjects\Capacity;
use App\Farm\Domain\ValueObjects\CropType;
use Illuminate\Support\Str;

class CreateStockService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository
    ) {}

    public function execute(
        string $warehouseId,
        ?string $harvestId,
        string $cropType,
        float $quantityKg,
        float $capacityUsed,
        float $capacityTotal,
        string $entryDate,
        ?string $notes = null,
    ): Stock {
        $stock = Stock::create(
            id: (string) Str::uuid(),
            warehouseId: $warehouseId,
            harvestId: $harvestId,
            cropType: CropType::fromString($cropType),
            quantityKg: $quantityKg,
            capacity: Capacity::fromValues($capacityUsed, $capacityTotal),
            entryDate: new \DateTimeImmutable($entryDate),
            notes: $notes,
        );

        $this->stockRepository->save($stock);

        return $stock;
    }
}
