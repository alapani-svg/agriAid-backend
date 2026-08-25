<?php

namespace App\Warehouse\Domain\ValueObjects;

enum WarehouseStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function canReceiveStock(): bool
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
