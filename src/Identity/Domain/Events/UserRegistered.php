<?php

namespace Src\Identity\Domain\Events;

use DateTimeImmutable;
use Src\Shared\Contracts\DomainEvent;

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        public string $userId,
        public string $email
    ) {}

    public function occurredOn(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
