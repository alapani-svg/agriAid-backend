<?php

namespace App\Farmer\Domain\ValueObjects;

final readonly class FarmSize
{
    private function __construct(
        public float $value
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Farm size cannot be negative');
        }
        if ($value > 10000) {
            throw new \InvalidArgumentException('Farm size cannot exceed 10,000 hectares');
        }
    }

    public static function fromHectares(float $hectares): self
    {
        return new self($hectares);
    }

    public function toHectares(): float
    {
        return $this->value;
    }

    public function equals(FarmSize $other): bool
    {
        return $this->value === $other->value;
    }
}
