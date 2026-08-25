<?php

namespace App\Shared\Domain;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
