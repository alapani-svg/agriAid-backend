<?php

namespace Src\Identity\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Email
{
    public function __construct(
        private string $value
    ) {
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $email): bool
    {
        return strtolower($this->value)
            === strtolower($email->value());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
