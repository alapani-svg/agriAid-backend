<?php

namespace App\Farm\Domain\ValueObjects;

final readonly class Quantity
{
    private function __construct(
        public float $value,
        public string $unit = 'kg'
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Quantity cannot be negative');
        }
        if ($value > 1000000) {
            throw new \InvalidArgumentException('Quantity cannot exceed 1,000,000 kg');
        }
    }

    public static function fromKilograms(float $kilograms): self
    {
        return new self($kilograms, 'kg');
    }

    public static function fromMetricTons(float $tons): self
    {
        return new self($tons * 1000, 'kg');
    }

    public function toKilograms(): float
    {
        return $this->value;
    }

    public function toMetricTons(): float
    {
        return $this->value / 1000;
    }

    public function equals(Quantity $other): bool
    {
        return $this->value === $other->value && $this->unit === $other->unit;
    }

    public function add(Quantity $other): Quantity
    {
        return new self($this->value + $other->value, $this->unit);
    }

    public function subtract(Quantity $other): Quantity
    {
        $newValue = $this->value - $other->value;
        if ($newValue < 0) {
            throw new \InvalidArgumentException('Result cannot be negative');
        }
        return new self($newValue, $this->unit);
    }
}
