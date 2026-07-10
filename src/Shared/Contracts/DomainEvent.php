<?php

namespace Src\Shared\Contracts;

interface DomainEvent
{
    public function occurredOn(): \DateTimeImmutable;
}
