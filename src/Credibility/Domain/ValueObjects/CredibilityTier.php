<?php

namespace App\Credibility\Domain\ValueObjects;

enum CredibilityTier: string
{
    case BUILDING = 'building';
    case ESTABLISHED = 'established';
    case STRONG = 'strong';
    case HIGH = 'high';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 85 => self::HIGH,
            $score >= 70 => self::STRONG,
            $score >= 40 => self::ESTABLISHED,
            default => self::BUILDING,
        };
    }

    /**
     * Maximum financing term (in years) associated with this tier, capped at 20 years.
     */
    public function maxFinancingTermYears(): int
    {
        return match ($this) {
            self::BUILDING => 1,
            self::ESTABLISHED => 5,
            self::STRONG => 10,
            self::HIGH => 20,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::BUILDING => 'Building',
            self::ESTABLISHED => 'Established',
            self::STRONG => 'Strong',
            self::HIGH => 'High',
        };
    }

    public function toString(): string
    {
        return $this->value;
    }
}
