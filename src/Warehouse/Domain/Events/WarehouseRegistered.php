<?php

namespace App\Warehouse\Domain\Events;

use App\Warehouse\Domain\Entities\Warehouse;
use App\Shared\Domain\Events\DomainEvent;

final readonly class WarehouseRegistered implements DomainEvent
{
    public function __construct(
        private Warehouse $warehouse
    ) {}

    public function getWarehouse(): Warehouse
    {
        return $this->warehouse;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->warehouse->getCreatedAt();
    }
}
