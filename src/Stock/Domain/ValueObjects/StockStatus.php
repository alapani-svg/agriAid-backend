<?php

namespace App\Stock\Domain\ValueObjects;

enum StockStatus: string
{
    case IN_STOCK = 'in_stock';
    case RESERVED = 'reserved';
    case WITHDRAWN = 'withdrawn';
    case SOLD = 'sold';

    public function canBeReserved(): bool
    {
        return $this === self::IN_STOCK;
    }

    public function canBeWithdrawn(): bool
    {
        return $this === self::IN_STOCK || $this === self::RESERVED;
    }

    public function canBeSold(): bool
    {
        return $this === self::IN_STOCK || $this === self::RESERVED;
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
