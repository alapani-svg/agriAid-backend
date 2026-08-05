<?php

namespace App\Stock\Domain\Events;

use App\Stock\Domain\Entities\Stock;
use App\Shared\Domain\Events\DomainEvent;

final readonly class StockCreated implements DomainEvent
{
    public function __construct(
        private Stock $stock
    ) {}

    public function getStock(): Stock
    {
        return $this->stock;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->stock->getCreatedAt();
    }
}
