<?php

namespace App\Identity\Domain\Events;

use App\Identity\Domain\Entities\User;
use App\Shared\Domain\DomainEvent;

final readonly class UserRegistered implements DomainEvent
{
    public function __construct(
        private User $user,
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->user->getCreatedAt();
    }
}
