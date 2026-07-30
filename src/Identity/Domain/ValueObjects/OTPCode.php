<?php

namespace App\Identity\Domain\ValueObjects;

use InvalidArgumentException;

class OTPCode
{
    private function __construct(
        private readonly string $value,
    ) {
        $this->validate($value);
    }

    public static function fromString(string $code): self
    {
        return new self($code);
    }

    public static function generate(int $length = 6): self
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }

        return new self($code);
    }

    private function validate(string $code): void
    {
        if (!preg_match('/^\d{6}$/', $code)) {
            throw new InvalidArgumentException('OTP code must be exactly 6 digits');
        }
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(OTPCode $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
