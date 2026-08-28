<?php

namespace App\Store\Domain\ValueObjects;

enum StoreOrderStatus: string
{
    case PENDING = 'pending';
    case FARMER_CONFIRMED = 'farmer_confirmed';
    case CONFIRMED = 'confirmed';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function toString(): string
    {
        return $this->value;
    }

    public static function fromString(string $status): self
    {
        return self::from($status);
    }

    /** Valid forward transitions from a given status. */
    public function nextValidStatuses(): array
    {
        return match ($this) {
            self::PENDING => [self::FARMER_CONFIRMED, self::CANCELLED],
            self::FARMER_CONFIRMED => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::SHIPPED, self::CANCELLED],
            self::SHIPPED => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED => [self::COMPLETED],
            self::COMPLETED, self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->nextValidStatuses(), true);
    }
}
