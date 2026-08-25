<?php

namespace App\Farm\Application\Services;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\Exceptions\HarvestNotFoundException;
use App\Stock\Application\Services\AutoUpdateStockOnHarvestStored;
use App\Receipt\Application\Services\IssueWarehouseReceiptService;
use App\Receipt\Domain\Entities\WarehouseReceipt;

class StoreHarvestInWarehouseService
{
    public function __construct(
        private readonly HarvestRepositoryInterface $harvestRepository,
        private readonly AutoUpdateStockOnHarvestStored $autoUpdateStockOnHarvestStored,
        private readonly IssueWarehouseReceiptService $issueWarehouseReceiptService,
    ) {}

    /**
     * @return array{harvest: Harvest, receipt: WarehouseReceipt}
     */
    public function execute(string $harvestId, string $warehouseId): array
    {
        $harvest = $this->harvestRepository->findById($harvestId);

        if ($harvest === null) {
            throw new HarvestNotFoundException("Harvest not found: {$harvestId}");
        }

        if ($harvest->getWarehouseId() === null) {
            $harvest->sendToWarehouse($warehouseId);
        }

        // Validate capacity and create the stock entry before committing the harvest's
        // status change, so a capacity failure leaves the harvest untouched.
        $stock = $this->autoUpdateStockOnHarvestStored->execute($harvestId, $warehouseId);

        $harvest->storeInWarehouse();
        $this->harvestRepository->save($harvest);

        $receipt = $this->issueWarehouseReceiptService->execute(
            warehouseId: $warehouseId,
            farmerId: $harvest->getFarmerId(),
            cropType: $harvest->getCropType()->toString(),
            quantityKg: $harvest->getQuantity()->toKilograms(),
            stockId: $stock->getId(),
        );

        return [
            'harvest' => $harvest,
            'receipt' => $receipt,
        ];
    }
}
