<?php

namespace App\Shared\Domain\Events;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
