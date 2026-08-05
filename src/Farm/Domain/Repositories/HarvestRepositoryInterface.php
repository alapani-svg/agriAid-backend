<?php

namespace App\Farm\Domain\Repositories;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\ValueObjects\HarvestStatus;

interface HarvestRepositoryInterface
{
    public function save(Harvest $harvest): void;
    
    public function findById(string $id): ?Harvest;
    
    public function findByFarmerId(string $farmerId): array;
    
    public function findByWarehouseId(string $warehouseId): array;
    
    public function findByStatus(HarvestStatus $status): array;
    
    public function delete(Harvest $harvest): void;
}
