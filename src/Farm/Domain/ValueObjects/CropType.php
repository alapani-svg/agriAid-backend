<?php

namespace App\Farm\Domain\ValueObjects;

final readonly class CropType
{
    private const VALID_CROPS = [
        'maize', 'rice', 'cassava', 'yam', 'plantain',
        'beans', 'groundnuts', 'soybeans', 'cocoa', 'coffee',
        'palm_oil', 'rubber', 'banana', 'tomato', 'onion',
        'pepper', 'vegetables', 'fruits', 'sorghum', 'millet'
    ];

    private function __construct(
        public string $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('Crop type cannot be empty');
        }
        if (!in_array(strtolower($value), self::VALID_CROPS, true)) {
            throw new \InvalidArgumentException("Invalid crop type: {$value}");
        }
    }

    public static function fromString(string $crop): self
    {
        return new self(strtolower($crop));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(CropType $other): bool
    {
        return $this->value === $other->value;
    }

    public static function getValidCrops(): array
    {
        return self::VALID_CROPS;
    }
}
