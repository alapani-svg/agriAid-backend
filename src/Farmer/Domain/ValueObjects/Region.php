<?php

namespace App\Farmer\Domain\ValueObjects;

final readonly class Region
{
    private const CAMEROON_REGIONS = [
        'Adamawa', 'Centre', 'East', 'Far North', 'Littoral', 
        'North', 'Northwest', 'South', 'Southwest', 'West'
    ];

    private function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Region cannot be empty');
        }
        if (!in_array($value, self::CAMEROON_REGIONS, true)) {
            throw new \InvalidArgumentException('Invalid Cameroon region');
        }
    }

    public static function fromString(string $region): self
    {
        return new self($region);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public static function getAvailableRegions(): array
    {
        return self::CAMEROON_REGIONS;
    }

    public function equals(Region $other): bool
    {
        return $this->value === $other->value;
    }
}
