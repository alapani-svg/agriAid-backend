<?php

namespace App\Receipt\Domain\Events;

use App\Receipt\Domain\Entities\WarehouseReceipt;
use App\Shared\Domain\Events\DomainEvent;

final readonly class WarehouseReceiptIssued implements DomainEvent
{
    public function __construct(
        private WarehouseReceipt $receipt
    ) {}

    public function getReceipt(): WarehouseReceipt
    {
        return $this->receipt;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->receipt->getCreatedAt();
    }
}
