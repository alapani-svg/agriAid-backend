<?php

namespace App\Store\Presentation\DTOs;

final readonly class CreateStoreOrderRequest
{
    public function __construct(
        public string $stockId,
        public float $quantityKg,
        public ?float $pricePerKg = null,
        public ?string $notes = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stockId: (string) ($data['stock_id'] ?? ''),
            quantityKg: (float) ($data['quantity_kg'] ?? 0),
            pricePerKg: isset($data['price_per_kg']) ? (float) $data['price_per_kg'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
        );
    }

    /**
     * @return array<string, string[]>
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->stockId)) {
            $errors['stock_id'] = ['Stock ID is required'];
        }

        if ($this->quantityKg <= 0) {
            $errors['quantity_kg'] = ['Quantity must be greater than 0'];
        }

        if ($this->pricePerKg !== null && $this->pricePerKg < 0) {
            $errors['price_per_kg'] = ['Price per kg cannot be negative'];
        }

        return $errors;
    }
}
