<?php

namespace App\Farm\Domain\Events;

use App\Farm\Domain\Entities\Harvest;
use App\Shared\Domain\Events\DomainEvent;

final readonly class HarvestRecorded implements DomainEvent
{
    public function __construct(
        private Harvest $harvest
    ) {}

    public function getHarvest(): Harvest
    {
        return $this->harvest;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->harvest->getCreatedAt();
    }
}
