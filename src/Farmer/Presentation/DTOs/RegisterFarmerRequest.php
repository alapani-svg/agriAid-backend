<?php

namespace App\Farmer\Presentation\DTOs;

readonly class RegisterFarmerRequest
{
    public function __construct(
        public string $userId,
        public string $farmName,
        public float $farmSize,
        public array $crops,
        public string $region,
        public string $village,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $cooperativeName = null,
        public ?string $cooperativeId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            farmName: $data['farm_name'],
            farmSize: (float) $data['farm_size'],
            crops: (array) $data['crops'],
            region: $data['region'],
            village: $data['village'],
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            cooperativeName: $data['cooperative_name'] ?? null,
            cooperativeId: $data['cooperative_id'] ?? null,
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->userId)) {
            $errors['user_id'] = 'User ID is required';
        }

        if (empty($this->farmName)) {
            $errors['farm_name'] = 'Farm name is required';
        }

        if ($this->farmSize <= 0) {
            $errors['farm_size'] = 'Farm size must be positive';
        }

        if (empty($this->crops)) {
            $errors['crops'] = 'At least one crop is required';
        }

        if (empty($this->region)) {
            $errors['region'] = 'Region is required';
        }

        if (empty($this->village)) {
            $errors['village'] = 'Village is required';
        }

        return $errors;
    }
}
