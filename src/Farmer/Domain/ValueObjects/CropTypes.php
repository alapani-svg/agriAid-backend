<?php

namespace App\Farmer\Domain\ValueObjects;

final readonly class CropTypes
{
    private const VALID_CROPS = [
        'maize', 'rice', 'cassava', 'yam', 'plantain',
        'beans', 'groundnuts', 'soybeans', 'cocoa', 'coffee',
        'palm_oil', 'rubber', 'banana', 'tomato', 'onion',
        'pepper', 'vegetables', 'fruits', 'sorghum', 'millet'
    ];

    private function __construct(
        public array $value
    ) {
        if (empty($value)) {
            throw new \InvalidArgumentException('At least one crop type is required');
        }
        
        foreach ($value as $crop) {
            if (!in_array($crop, self::VALID_CROPS, true)) {
                throw new \InvalidArgumentException("Invalid crop type: {$crop}");
            }
        }
    }

    public static function fromArray(array $crops): self
    {
        $uniqueCrops = array_unique(array_map('strtolower', $crops));
        return new self(array_values($uniqueCrops));
    }

    public function toArray(): array
    {
        return $this->value;
    }

    public function hasCrop(string $crop): bool
    {
        return in_array(strtolower($crop), $this->value, true);
    }

    public function count(): int
    {
        return count($this->value);
    }

    public static function getValidCrops(): array
    {
        return self::VALID_CROPS;
    }

    public function equals(CropTypes $other): bool
    {
        return $this->value === $other->value;
    }
}
