<?php

namespace App\Farm\Application\Services;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\Repositories\HarvestRepositoryInterface;
use App\Farm\Domain\Exceptions\HarvestNotFoundException;

class SendHarvestToWarehouseService
{
    public function __construct(
        private readonly HarvestRepositoryInterface $harvestRepository
    ) {}

    public function execute(
        string $harvestId,
        string $warehouseId,
    ): Harvest {
        $harvest = $this->harvestRepository->findById($harvestId);

        if ($harvest === null) {
            throw new HarvestNotFoundException("Harvest not found: {$harvestId}");
        }

        $harvest->sendToWarehouse($warehouseId);
        $this->harvestRepository->save($harvest);

        return $harvest;
    }
}
