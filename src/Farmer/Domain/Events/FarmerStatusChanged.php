<?php

namespace App\Farmer\Domain\Events;

use App\Farmer\Domain\Entities\Farmer;
use App\Farmer\Domain\ValueObjects\FarmerStatus;
use App\Shared\Domain\Events\DomainEvent;

final readonly class FarmerStatusChanged implements DomainEvent
{
    public function __construct(
        private Farmer $farmer,
        private FarmerStatus $newStatus
    ) {}

    public function getFarmer(): Farmer
    {
        return $this->farmer;
    }

    public function getNewStatus(): FarmerStatus
    {
        return $this->newStatus;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->farmer->getUpdatedAt() ?? $this->farmer->getCreatedAt();
    }
}
