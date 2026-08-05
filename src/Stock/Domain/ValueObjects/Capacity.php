<?php

namespace App\Stock\Domain\ValueObjects;

final readonly class Capacity
{
    private function __construct(
        public float $used,
        public float $total
    ) {
        if ($total <= 0) {
            throw new \InvalidArgumentException('Total capacity must be positive');
        }
        if ($used < 0) {
            throw new \InvalidArgumentException('Used capacity cannot be negative');
        }
        if ($used > $total) {
            throw new \InvalidArgumentException('Used capacity cannot exceed total capacity');
        }
    }

    public static function fromValues(float $used, float $total): self
    {
        return new self($used, $total);
    }

    public function getUsed(): float
    {
        return $this->used;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function getAvailable(): float
    {
        return $this->total - $this->used;
    }

    public function getUtilizationPercentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }
        return ($this->used / $this->total) * 100;
    }

    public function hasCapacityFor(float $additional): bool
    {
        return ($this->used + $additional) <= $this->total;
    }

    public function addUsed(float $additional): self
    {
        $newUsed = $this->used + $additional;
        if ($newUsed > $this->total) {
            throw new \InvalidArgumentException('Adding this amount would exceed total capacity');
        }
        return new self($newUsed, $this->total);
    }

    public function subtractUsed(float $amount): self
    {
        $newUsed = $this->used - $amount;
        if ($newUsed < 0) {
            throw new \InvalidArgumentException('Subtracting this amount would result in negative used capacity');
        }
        return new self($newUsed, $this->total);
    }

    public function isFull(): bool
    {
        return $this->used >= $this->total;
    }

    public function isEmpty(): bool
    {
        return $this->used === 0.0;
    }

    public function equals(Capacity $other): bool
    {
        return $this->used === $other->used && $this->total === $other->total;
    }
}
