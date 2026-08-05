<?php

namespace App\Farmer\Domain\ValueObjects;

enum FarmerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';

    public function canRegisterHarvest(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canAccessPlatform(): bool
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
