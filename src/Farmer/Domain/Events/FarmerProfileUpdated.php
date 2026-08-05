<?php

namespace App\Farmer\Domain\Events;

use App\Farmer\Domain\Entities\Farmer;
use App\Shared\Domain\Events\DomainEvent;

final readonly class FarmerProfileUpdated implements DomainEvent
{
    public function __construct(
        private Farmer $farmer
    ) {}

    public function getFarmer(): Farmer
    {
        return $this->farmer;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->farmer->getUpdatedAt() ?? $this->farmer->getCreatedAt();
    }
}
