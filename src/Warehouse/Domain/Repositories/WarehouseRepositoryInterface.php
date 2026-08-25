<?php

namespace App\Warehouse\Domain\Repositories;

use App\Warehouse\Domain\Entities\Warehouse;

interface WarehouseRepositoryInterface
{
    public function save(Warehouse $warehouse): void;

    public function findById(string $id): ?Warehouse;

    public function findByRegion(string $region): array;

    public function findAllActive(): array;

    public function findAll(): array;

    public function findByManagerUserId(string $managerUserId): array;

    public function findByFarmerId(string $farmerId): array;

    public function delete(Warehouse $warehouse): void;
}
