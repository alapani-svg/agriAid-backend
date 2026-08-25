<?php

namespace App\Receipt\Domain\Repositories;

use App\Receipt\Domain\Entities\WarehouseReceipt;

interface WarehouseReceiptRepositoryInterface
{
    public function save(WarehouseReceipt $receipt): void;

    public function findById(string $id): ?WarehouseReceipt;

    public function findByReceiptNumber(string $receiptNumber): ?WarehouseReceipt;

    public function findByFarmerId(string $farmerId): array;

    public function findByWarehouseId(string $warehouseId): array;

    public function delete(WarehouseReceipt $receipt): void;
}
