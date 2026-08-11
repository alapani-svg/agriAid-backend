<?php

namespace App\Farmer\Presentation\DTOs;

readonly class UpdateFarmerProfileRequest
{
    public function __construct(
        public ?string $farmName = null,
        public ?float $farmSize = null,
        public ?array $crops = null,
        public ?string $region = null,
        public ?string $village = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $cooperativeName = null,
        public ?string $cooperativeId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            farmName: $data['farm_name'] ?? null,
            farmSize: isset($data['farm_size']) ? (float) $data['farm_size'] : null,
            crops: $data['crops'] ?? null,
            region: $data['region'] ?? null,
            village: $data['village'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            cooperativeName: $data['cooperative_name'] ?? null,
            cooperativeId: $data['cooperative_id'] ?? null,
        );
    }
}
