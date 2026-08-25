<?php

namespace App\Credibility\Domain\ValueObjects;

final readonly class CredibilityScore
{
    private function __construct(
        public int $value,
        public CredibilityTier $tier,
    ) {}

    public static function fromValue(int $value): self
    {
        if ($value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Credibility score must be between 0 and 100');
        }

        return new self($value, CredibilityTier::fromScore($value));
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getTier(): CredibilityTier
    {
        return $this->tier;
    }

    public function getMaxFinancingTermYears(): int
    {
        return $this->tier->maxFinancingTermYears();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->value,
            'tier' => $this->tier->toString(),
            'tier_label' => $this->tier->label(),
            'max_financing_term_years' => $this->getMaxFinancingTermYears(),
        ];
    }
}
