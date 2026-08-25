<?php

namespace App\Receipt\Domain\ValueObjects;

enum ReceiptStatus: string
{
    case ACTIVE = 'active';
    case REDEEMED = 'redeemed';
    case CANCELLED = 'cancelled';

    public function canBeRedeemed(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canBeCancelled(): bool
    {
        return $this === self::ACTIVE;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $status): self
    {
        return self::from($status);
    }
}
