<?php

namespace Src\Identity\Domain\Entities;

use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\ValueObjects\Email;
use Src\Shared\Contracts\AggregateRoot;

class User extends AggregateRoot
{
    private function __construct(
        private readonly string $id,
        private string $name,
        private Email $email,
        private string $passwordHash,
    ) {}
}
