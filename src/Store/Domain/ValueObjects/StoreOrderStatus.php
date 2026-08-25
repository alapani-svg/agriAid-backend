<?php

namespace App\Store\Domain\ValueObjects;

enum StoreOrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $status): self
    {
        return self::from($status);
    }
}
