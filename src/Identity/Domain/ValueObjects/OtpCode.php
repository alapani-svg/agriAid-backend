<?php

namespace Src\Identity\Domain\ValueObjects;

use InvalidArgumentException;

/** Six-digit OTP value object for identity verification. */
final class OtpCode
{
    private function __construct(
        private readonly string $value,
    ) {}

    public static function fromString(string $code): self
    {
        $code = trim($code);
        if (! preg_match('/^\d{6}$/', $code)) {
            throw new InvalidArgumentException('OTP must be exactly 6 digits.');
        }

        return new self($code);
    }

    public static function generate(): self
    {
        return new self(str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }
}
