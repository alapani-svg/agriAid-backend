<?php

namespace App\Store\Domain\Repositories;

use App\Store\Domain\Entities\StoreOrder;

interface StoreOrderRepositoryInterface
{
    public function save(StoreOrder $order): void;

    public function findById(string $id): ?StoreOrder;

    public function findByBuyerId(string $buyerId): array;

    public function findByStockId(string $stockId): array;

    public function findAll(): array;

    public function delete(StoreOrder $order): void;
}
