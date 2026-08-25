<?php

namespace App\Warehouse\Application\Services;

use App\Warehouse\Domain\Entities\Warehouse;
use App\Warehouse\Domain\Repositories\WarehouseRepositoryInterface;
use Illuminate\Support\Str;

class RegisterWarehouseService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function execute(
        string $name,
        string $region,
        float $capacityTotalKg,
        ?string $managerUserId = null,
        ?string $farmerId = null,
        ?string $village = null,
        ?string $address = null,
        ?string $notes = null,
    ): Warehouse {
        $warehouse = Warehouse::register(
            id: (string) Str::uuid(),
            name: $name,
            region: $region,
            capacityTotalKg: $capacityTotalKg,
            managerUserId: $managerUserId,
            farmerId: $farmerId,
            village: $village,
            address: $address,
            notes: $notes,
        );

        $this->warehouseRepository->save($warehouse);

        return $warehouse;
    }
}
