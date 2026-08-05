<?php

namespace App\Farm\Domain\ValueObjects;

final readonly class QualityGrade
{
    private function __construct(
        public float $value
    ) {
        if ($value < 1.0 || $value > 5.0) {
            throw new \InvalidArgumentException('Quality grade must be between 1.0 and 5.0');
        }
    }

    public static function fromScore(float $score): self
    {
        return new self($score);
    }

    public function toScore(): float
    {
        return $this->value;
    }

    public function equals(QualityGrade $other): bool
    {
        return $this->value === $other->value;
    }

    public function getRating(): string
    {
        return match(true) {
            $this->value >= 4.5 => 'Excellent',
            $this->value >= 3.5 => 'Good',
            $this->value >= 2.5 => 'Fair',
            $this->value >= 1.5 => 'Poor',
            default => 'Very Poor',
        };
    }
}
