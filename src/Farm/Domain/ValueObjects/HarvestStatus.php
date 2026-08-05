<?php

namespace App\Farm\Domain\ValueObjects;

enum HarvestStatus: string
{
    case HARVESTED = 'harvested';
    case IN_TRANSIT = 'in_transit';
    case STORED = 'stored';
    case SOLD = 'sold';

    public function canBeStored(): bool
    {
        return $this === self::HARVESTED || $this === self::IN_TRANSIT;
    }

    public function canBeSold(): bool
    {
        return $this === self::STORED;
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
