<?php

namespace App\Store\Presentation\DTOs;

final readonly class CreateStoreOrderRequest
{
    public function __construct(
        public string $stockId,
        public float $quantityKg,
        public ?float $pricePerKg = null,
        public ?string $notes = null,
        public ?string $deliveryMethod = null,
        public ?string $deliveryAddress = null,
        public ?string $deliveryCity = null,
        public ?string $deliveryPhone = null,
        public ?string $deliveryNotes = null,
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
            deliveryMethod: isset($data['delivery_method']) ? (string) $data['delivery_method'] : null,
            deliveryAddress: isset($data['delivery_address']) ? (string) $data['delivery_address'] : null,
            deliveryCity: isset($data['delivery_city']) ? (string) $data['delivery_city'] : null,
            deliveryPhone: isset($data['delivery_phone']) ? (string) $data['delivery_phone'] : null,
            deliveryNotes: isset($data['delivery_notes']) ? (string) $data['delivery_notes'] : null,
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

        $validDeliveryMethods = ['pickup', 'delivery', 'transport'];
        if ($this->deliveryMethod !== null && !in_array($this->deliveryMethod, $validDeliveryMethods, true)) {
            $errors['delivery_method'] = ['Delivery method must be one of: pickup, delivery, transport'];
        }

        if ($this->deliveryMethod === 'delivery' && empty($this->deliveryAddress)) {
            $errors['delivery_address'] = ['Delivery address is required for delivery'];
        }

        if ($this->deliveryMethod === 'delivery' && empty($this->deliveryPhone)) {
            $errors['delivery_phone'] = ['Delivery phone is required for delivery'];
        }

        return $errors;
    }
}
