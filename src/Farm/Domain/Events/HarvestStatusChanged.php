<?php

namespace App\Farm\Domain\Events;

use App\Farm\Domain\Entities\Harvest;
use App\Farm\Domain\ValueObjects\HarvestStatus;
use App\Shared\Domain\Events\DomainEvent;

final readonly class HarvestStatusChanged implements DomainEvent
{
    public function __construct(
        private Harvest $harvest,
        private HarvestStatus $newStatus
    ) {}

    public function getHarvest(): Harvest
    {
        return $this->harvest;
    }

    public function getNewStatus(): HarvestStatus
    {
        return $this->newStatus;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->harvest->getUpdatedAt() ?? $this->harvest->getCreatedAt();
    }
}
