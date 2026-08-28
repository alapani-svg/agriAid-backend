<?php

namespace App\Stock\Domain\Repositories;

use App\Stock\Domain\Entities\Stock;
use App\Stock\Domain\ValueObjects\StockStatus;

interface StockRepositoryInterface
{
    public function save(Stock $stock): void;
    
    public function findById(string $id): ?Stock;
    
    public function findByWarehouseId(string $warehouseId): array;
    
    public function findByHarvestId(string $harvestId): ?Stock;

    public function findByFarmerId(string $farmerId): array;
    
    public function findByStatus(StockStatus $status): array;
    
    public function findAll(): array;
    
    public function delete(Stock $stock): void;
}
